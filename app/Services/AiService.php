<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Optional AI assistant. Configured from Admin → Settings → AI with a provider
 * (anthropic | openai) and API key. Every method fails soft: when no key is set
 * or the call errors it returns a sensible local fallback, so nothing in the app
 * ever breaks because AI is unavailable.
 */
class AiService
{
    public function enabled(): bool
    {
        return (bool) setting('ai_key');
    }

    /** Suggest a short, memorable alias for a URL. */
    public function suggestAlias(string $url, ?string $title = null): string
    {
        $prompt = "Suggest ONE short, lowercase, memorable URL slug (3-20 chars, letters/numbers/hyphens only, no spaces) for this link. Reply with ONLY the slug.\nURL: {$url}\nTitle: " . ($title ?: 'n/a');
        $out = $this->complete($prompt, 20);
        $slug = Str::slug(preg_replace('/[^a-zA-Z0-9\- ]/', '', (string) $out));

        return $slug !== '' ? Str::limit($slug, 20, '') : Str::lower(Str::random(6));
    }

    /** Suggest up to 5 tags for a link. */
    public function suggestTags(string $url, ?string $title = null): array
    {
        $prompt = "Give 3-5 short lowercase topic tags (single words) for this link as a comma-separated list, no explanation.\nURL: {$url}\nTitle: " . ($title ?: 'n/a');
        $out = $this->complete($prompt, 40);
        if (! $out) {
            return [];
        }

        return collect(explode(',', $out))
            ->map(fn ($t) => Str::slug(trim($t)))
            ->filter()->take(5)->values()->all();
    }

    /** Spam/abuse likelihood 0..100 for a destination URL. */
    public function spamScore(string $url): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $prompt = "Rate the spam/phishing/malware likelihood of this URL from 0 (safe) to 100 (definitely malicious). Reply with ONLY the number.\nURL: {$url}";
        $out = $this->complete($prompt, 8);

        return max(0, min(100, (int) preg_replace('/\D/', '', (string) $out)));
    }

    /** Draft a short bio/about blurb from a few words about the user. */
    public function bio(string $about): string
    {
        if (! $this->enabled()) {
            return '';
        }
        $prompt = "Write a friendly, professional bio of at most 2 short sentences (under 220 characters) for a personal link-in-bio page, based on these notes. Reply with ONLY the bio text, no quotes or preamble.\nNotes: {$about}";

        return (string) ($this->complete($prompt, 160) ?? '');
    }

    /** Suggest a concise, click-worthy title for a URL. */
    public function title(string $url, ?string $context = null): string
    {
        if (! $this->enabled()) {
            return '';
        }
        $prompt = "Suggest ONE concise, click-worthy title (max 60 characters) for the link below. Reply with ONLY the title, no quotes or preamble.\nURL: {$url}\nContext: " . ($context ?: 'n/a');

        return (string) ($this->complete($prompt, 40) ?? '');
    }

    /** Low-level completion call. Returns null on any failure (caller falls back). */
    protected function complete(string $prompt, int $maxTokens = 64): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $provider = setting('ai_provider', 'anthropic');
        $key = setting('ai_key');

        try {
            if ($provider === 'openai') {
                $res = Http::withToken($key)->timeout(15)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => setting('ai_model', 'gpt-4o-mini'),
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.4,
                ]);

                return $res->ok() ? trim((string) $res->json('choices.0.message.content')) : null;
            }

            // default: Anthropic Claude
            $res = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model' => setting('ai_model', 'claude-haiku-4-5-20251001'),
                'max_tokens' => $maxTokens,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            return $res->ok() ? trim((string) $res->json('content.0.text')) : null;
        } catch (\Throwable $e) {
            Log::warning('AI call failed: ' . $e->getMessage());

            return null;
        }
    }
}
