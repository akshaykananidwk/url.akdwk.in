<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;

/**
 * QR generation with styling options.
 * Options: fg (#hex), bg (#hex), size (px), ec_level (low|medium|quartile|high),
 * logo (storage path), frame_text (call-to-action under the code).
 */
class QrService
{
    public function png(string $data, array $options = []): string
    {
        return $this->build($data, $options, new PngWriter())->getString();
    }

    public function svg(string $data, array $options = []): string
    {
        return $this->build($data, $options, new SvgWriter())->getString();
    }

    /** A4-ish PDF wrapping the PNG, with optional frame text. */
    public function pdf(string $data, array $options = [], string $title = 'QR Code'): string
    {
        $png = base64_encode($this->png($data, $options + ['size' => 600]));
        $frame = e($options['frame_text'] ?? '');
        $html = '<html><body style="text-align:center;font-family:sans-serif;padding-top:60px;">'
            . '<img src="data:image/png;base64,' . $png . '" style="width:400px;height:auto;">'
            . ($frame !== '' ? '<div style="font-size:28px;font-weight:bold;margin-top:24px;">' . $frame . '</div>' : '')
            . '<div style="font-size:12px;color:#888;margin-top:40px;">' . e($title) . '</div>'
            . '</body></html>';

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }

    protected function build(string $data, array $options, $writer)
    {
        $fg = $this->hexToColor($options['fg'] ?? '#000000');
        $bg = $this->hexToColor($options['bg'] ?? '#ffffff');
        $size = min(1200, max(120, (int) ($options['size'] ?? 400)));

        $ec = match ($options['ec_level'] ?? 'medium') {
            'low' => ErrorCorrectionLevel::Low,
            'quartile' => ErrorCorrectionLevel::Quartile,
            'high' => ErrorCorrectionLevel::High,
            default => ErrorCorrectionLevel::Medium,
        };

        $logoPath = null;
        if (! empty($options['logo'])) {
            $disk = Storage::disk(setting('storage_disk', 'public'));
            if ($disk->exists($options['logo'])) {
                // Endroid needs a local file path; pull remote disks to a temp file.
                $logoPath = $disk->path($options['logo'] ?? '');
                if (! is_file($logoPath)) {
                    $tmp = tempnam(sys_get_temp_dir(), 'qrlogo');
                    file_put_contents($tmp, $disk->get($options['logo']));
                    $logoPath = $tmp;
                }
            }
        }

        $builder = new Builder(
            writer: $writer,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: $logoPath ? ErrorCorrectionLevel::High : $ec,
            size: $size,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: $fg,
            backgroundColor: $bg,
            logoPath: $logoPath ?? '',
            logoResizeToWidth: $logoPath ? (int) ($size * 0.22) : null,
            logoPunchoutBackground: (bool) $logoPath,
        );

        return $builder->build();
    }

    protected function hexToColor(string $hex): Color
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $int = hexdec(substr($hex, 0, 6));

        return new Color(($int >> 16) & 255, ($int >> 8) & 255, $int & 255);
    }
}
