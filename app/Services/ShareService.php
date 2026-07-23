<?php

namespace App\Services;

/**
 * Builds share-intent URLs for the common social channels.
 * All returned URLs are fully rawurlencode()'d and safe to drop into an href.
 */
class ShareService
{
    public static function whatsapp(string $url, string $text = ''): string
    {
        $message = trim($text . ' ' . $url);

        return 'https://api.whatsapp.com/send?text=' . rawurlencode($message);
    }

    public static function twitter(string $url, string $text = ''): string
    {
        return 'https://twitter.com/intent/tweet?url=' . rawurlencode($url)
            . '&text=' . rawurlencode($text);
    }

    public static function facebook(string $url): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);
    }

    public static function telegram(string $url, string $text = ''): string
    {
        return 'https://t.me/share/url?url=' . rawurlencode($url)
            . '&text=' . rawurlencode($text);
    }

    public static function linkedin(string $url): string
    {
        return 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url);
    }

    public static function email(string $url, string $subject = '', string $body = ''): string
    {
        $body = trim($body === '' ? $url : $body . "\n\n" . $url);

        return 'mailto:?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
    }

    public static function reddit(string $url, string $title = ''): string
    {
        return 'https://www.reddit.com/submit?url=' . rawurlencode($url)
            . '&title=' . rawurlencode($title);
    }

    /**
     * Every channel as an ordered array ready to loop in Blade.
     * WhatsApp is first — it is the dominant sharing channel in India.
     *
     * @return array<string, array{icon: string, label: string, href: string}>
     */
    public static function all(string $url, string $text = ''): array
    {
        return [
            'whatsapp' => [
                'icon' => 'phone',
                'label' => 'WhatsApp',
                'href' => self::whatsapp($url, $text),
            ],
            'twitter' => [
                'icon' => 'share',
                'label' => 'X / Twitter',
                'href' => self::twitter($url, $text),
            ],
            'facebook' => [
                'icon' => 'share',
                'label' => 'Facebook',
                'href' => self::facebook($url),
            ],
            'telegram' => [
                'icon' => 'share',
                'label' => 'Telegram',
                'href' => self::telegram($url, $text),
            ],
            'linkedin' => [
                'icon' => 'users',
                'label' => 'LinkedIn',
                'href' => self::linkedin($url),
            ],
            'reddit' => [
                'icon' => 'megaphone',
                'label' => 'Reddit',
                'href' => self::reddit($url, $text),
            ],
            'email' => [
                'icon' => 'mail',
                'label' => 'Email',
                'href' => self::email($url, $text, $text),
            ],
        ];
    }
}
