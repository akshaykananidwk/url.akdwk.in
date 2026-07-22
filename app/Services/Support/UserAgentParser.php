<?php

namespace App\Services\Support;

/**
 * Lightweight, dependency-free user agent parser.
 * Detects OS, browser, device class, and bots/crawlers.
 */
class UserAgentParser
{
    protected string $ua;

    public function __construct(?string $userAgent)
    {
        $this->ua = $userAgent ?? '';
    }

    public static function parse(?string $userAgent): array
    {
        $p = new static($userAgent);

        return [
            'os' => $p->os(),
            'browser' => $p->browser(),
            'device' => $p->device(),
            'is_bot' => $p->isBot(),
        ];
    }

    public function isBot(): bool
    {
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|curl\/|wget|python-requests|httpclient|facebookexternalhit|whatsapp|telegrambot|twitterbot|linkedinbot|pinterestbot|discordbot|slackbot|skypeuripreview|embedly|quora link preview|bitlybot|vkshare|w3c_validator|preview|headless|lighthouse|gtmetrix|pingdom|uptimerobot|monitis|ahrefsbot|semrushbot|mj12bot|dotbot|petalbot|bytespider|yandex|baiduspider|duckduckbot|applebot|ia_archiver/i',
            $this->ua
        );
    }

    public function os(): string
    {
        $ua = $this->ua;

        return match (true) {
            (bool) preg_match('/windows phone/i', $ua) => 'Windows Phone',
            (bool) preg_match('/windows nt|win64|win32/i', $ua) => 'Windows',
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/mac os x|macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/cros/i', $ua) => 'ChromeOS',
            (bool) preg_match('/linux|x11/i', $ua) => 'Linux',
            $ua === '' => 'Unknown',
            default => 'Other',
        };
    }

    public function browser(): string
    {
        $ua = $this->ua;

        return match (true) {
            (bool) preg_match('/edg(a|ios|e)?\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/ucbrowser/i', $ua) => 'UC Browser',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/brave/i', $ua) => 'Brave',
            (bool) preg_match('/vivaldi/i', $ua) => 'Vivaldi',
            (bool) preg_match('/crios|chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            (bool) preg_match('/msie|trident/i', $ua) => 'Internet Explorer',
            $ua === '' => 'Unknown',
            default => 'Other',
        };
    }

    /** desktop | mobile | tablet */
    public function device(): string
    {
        $ua = $this->ua;

        if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua)
            || (preg_match('/android/i', $ua) && ! preg_match('/mobile/i', $ua))) {
            return 'tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|blackberry|windows phone|opera mini/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
