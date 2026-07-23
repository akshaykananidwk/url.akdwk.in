<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * One-click self-updater.
 *
 * Pulls the latest code straight from GitHub and applies it over the live
 * install — no terminal, FTP or manual file editing. The admin configures the
 * source repo/branch once (and a token for private repos); after that a single
 * "Update now" button downloads the branch zipball, copies the files over
 * (preserving .env, storage/ and the database), runs migrations and clears
 * caches.
 */
class SelfUpdateController extends Controller
{
    /** Files/folders never overwritten by an update. */
    protected array $preserve = ['.env', '.git', 'storage', 'node_modules'];

    public function index()
    {
        return view('admin.update', [
            'repo' => setting('update_repo', 'akshaykananidwk/url.akdwk.in'),
            'branch' => setting('update_branch', 'claude/url-shortener-saas-platform-5pdayk'),
            'hasToken' => (bool) setting('update_token'),
            'deployed' => setting('deployed_commit'),
            'deployedAt' => setting('deployed_at'),
            'version' => config('install.version', '1.0.0'),
        ]);
    }

    /** Save the update source (repo / branch / optional token). */
    public function saveSource(Request $request)
    {
        $data = $request->validate([
            'repo' => 'required|string|max:120|regex:#^[\w.\-]+/[\w.\-]+$#',
            'branch' => 'required|string|max:120',
            'token' => 'nullable|string|max:255',
        ]);

        setting_set('update_repo', $data['repo']);
        setting_set('update_branch', $data['branch']);
        if (! empty($data['token']) && $data['token'] !== '••••••••') {
            setting_set('update_token', $data['token'], true); // encrypted at rest
        }

        return back()->with('status', __('Update source saved.'));
    }

    public function clearToken()
    {
        app(\App\Services\Support\SettingsRepository::class)->forget('update_token');

        return back()->with('status', __('Token removed.'));
    }

    /** AJAX: how does the live install compare to GitHub? */
    public function check()
    {
        try {
            $latest = $this->latestCommit();
            $deployed = setting('deployed_commit');

            return response()->json([
                'ok' => true,
                'up_to_date' => $deployed && $deployed === $latest['sha'],
                'deployed' => $deployed,
                'latest' => $latest,
                'commits' => $this->recentCommits(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Perform the update. Returns a JSON step log. */
    public function run(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $log = [];

        try {
            $latest = $this->latestCommit();
            $log[] = __('Latest version: :sha — :msg', ['sha' => substr($latest['sha'], 0, 7), 'msg' => $latest['message']]);

            $tmp = storage_path('app/self-update');
            File::deleteDirectory($tmp);
            File::ensureDirectoryExists($tmp);
            $zipPath = $tmp . '/update.zip';

            // 1. download the branch zipball (streamed to disk)
            $log[] = __('Downloading update…');
            $res = $this->client()->withOptions(['sink' => $zipPath])
                ->get("https://api.github.com/repos/{$this->repo()}/zipball/" . rawurlencode($this->branch()));
            if (! $res->successful() || ! is_file($zipPath) || filesize($zipPath) < 1000) {
                throw new \RuntimeException('Download failed (HTTP ' . $res->status() . '). Check repo/branch/token.');
            }
            $log[] = __('Downloaded :mb MB', ['mb' => round(filesize($zipPath) / 1048576, 1)]);

            // 2. extract
            $extractDir = $tmp . '/extract';
            File::ensureDirectoryExists($extractDir);
            $za = new ZipArchive();
            if ($za->open($zipPath) !== true) {
                throw new \RuntimeException('Could not open the downloaded archive.');
            }
            $za->extractTo($extractDir);
            $za->close();

            // GitHub zipballs contain a single top-level folder: owner-repo-<sha>/
            $roots = File::directories($extractDir);
            if (! $roots) {
                throw new \RuntimeException('Archive was empty.');
            }
            $source = $roots[0];

            // 3. copy files over (preserving env, storage, database)
            $log[] = __('Applying files…');
            $applied = $this->copyOver($source, base_path());
            $log[] = __(':count items updated', ['count' => $applied]);

            // 4. migrations + cache clear
            $log[] = __('Running database migrations…');
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            $log[] = __('Caches cleared.');

            // 5. record the new version
            setting_set('deployed_commit', $latest['sha']);
            setting_set('deployed_at', now()->toIso8601String());
            setting_set('installed_version', config('install.version', '1.0.0'));

            File::deleteDirectory($tmp);
            AuditLog::record('self_update.applied', null, ['sha' => $latest['sha']]);

            $log[] = '✅ ' . __('Update complete!');

            return response()->json(['ok' => true, 'log' => $log, 'sha' => substr($latest['sha'], 0, 7)]);
        } catch (\Throwable $e) {
            $log[] = '❌ ' . $e->getMessage();
            AuditLog::record('self_update.failed', null, ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'log' => $log, 'message' => $e->getMessage()], 422);
        }
    }

    /* ------------------------------------------------------------------ */

    protected function repo(): string
    {
        return trim((string) setting('update_repo', 'akshaykananidwk/url.akdwk.in'));
    }

    protected function branch(): string
    {
        return trim((string) setting('update_branch', 'claude/url-shortener-saas-platform-5pdayk'));
    }

    protected function client()
    {
        $http = Http::timeout(120)->withHeaders([
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Shortl-SelfUpdater',
            'X-GitHub-Api-Version' => '2022-11-28',
        ]);
        if ($token = setting('update_token')) {
            $http = $http->withToken($token);
        }

        return $http;
    }

    protected function latestCommit(): array
    {
        $res = $this->client()->get("https://api.github.com/repos/{$this->repo()}/commits/" . rawurlencode($this->branch()));
        if (! $res->successful()) {
            throw new \RuntimeException('Could not reach GitHub (HTTP ' . $res->status() . '). ' . ($res->json('message') ?? '') . ' — check the repo, branch and token.');
        }

        return [
            'sha' => $res->json('sha'),
            'message' => explode("\n", (string) $res->json('commit.message'))[0],
            'date' => $res->json('commit.author.date'),
            'url' => $res->json('html_url'),
        ];
    }

    protected function recentCommits(): array
    {
        $res = $this->client()->get("https://api.github.com/repos/{$this->repo()}/commits", [
            'sha' => $this->branch(), 'per_page' => 10,
        ]);
        if (! $res->successful()) {
            return [];
        }

        return collect($res->json())->map(fn ($c) => [
            'sha' => substr($c['sha'], 0, 7),
            'message' => explode("\n", $c['commit']['message'])[0],
            'date' => $c['commit']['author']['date'],
        ])->all();
    }

    /**
     * Recursively copy the extracted release over the app, skipping preserved
     * paths and never clobbering the live database. Additive: files removed
     * upstream are left in place (harmless).
     */
    protected function copyOver(string $from, string $to): int
    {
        $count = 0;
        foreach (scandir($from) as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $this->preserve, true)) {
                continue;
            }
            $src = $from . '/' . $item;
            $dst = $to . '/' . $item;

            if ($item === 'database') {
                // update migrations/seeders but keep any sqlite database file
                $count += $this->copyDir($src, $dst, ['database.sqlite']);
                continue;
            }

            if (is_dir($src)) {
                $count += $this->copyDir($src, $dst, []);
            } else {
                File::ensureDirectoryExists(dirname($dst));
                File::copy($src, $dst);
                $count++;
            }
        }

        return $count;
    }

    protected function copyDir(string $from, string $to, array $skip): int
    {
        File::ensureDirectoryExists($to);
        $count = 0;
        foreach (scandir($from) as $item) {
            if ($item === '.' || $item === '..' || in_array($item, $skip, true)) {
                continue;
            }
            $src = $from . '/' . $item;
            $dst = $to . '/' . $item;
            if (is_dir($src)) {
                $count += $this->copyDir($src, $dst, []);
            } else {
                File::copy($src, $dst);
                $count++;
            }
        }

        return $count;
    }
}
