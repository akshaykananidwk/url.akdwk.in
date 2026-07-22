<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Serves the self-contained GitHub update panel (public/admin.html) from
 * inside the authenticated admin area at /admin/updates, with a back link
 * injected. Keeping a single source file avoids Blade having to escape the
 * panel's raw CSS @media/@keyframes and JS template literals.
 */
class UpdatePanelController extends Controller
{
    public function index()
    {
        $path = public_path('admin.html');
        abort_unless(is_file($path), 404, 'Update panel file (public/admin.html) is missing.');

        $html = (string) file_get_contents($path);

        // Inject a "back to admin" bar right after <body> so it feels part of the panel.
        $bar = '<div style="background:#0b1220;padding:10px 14px;border-bottom:1px solid #1e293b;display:flex;gap:14px;align-items:center;flex-wrap:wrap">'
            . '<a href="' . e(route('admin.dashboard')) . '" style="color:#818cf8;text-decoration:none;font:600 14px system-ui,sans-serif">&larr; ' . e(__('Back to Admin')) . '</a>'
            . '<span style="color:#64748b;font:12px system-ui,sans-serif">' . e(__('Edit &amp; push your website files to GitHub')) . '</span></div>';
        $html = preg_replace('/<body[^>]*>/', '$0' . $bar, $html, 1);

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
