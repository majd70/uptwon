<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Builds the QR codes the restaurant prints: a generic one for the door and
 * per-table variants that carry ?table=N through to qr_scans.
 *
 * SVG comes from simple-qrcode. PNG is rasterised here from the same
 * bacon-qr-code matrix using GD, because simple-qrcode's PNG backend requires
 * the Imagick extension, which this XAMPP build does not ship.
 */
class QrCodeBuilder
{
    /** Quiet zone in modules. 4 is the spec minimum for reliable scanning. */
    private const MARGIN = 4;

    /** The URL a scan should land on. */
    public static function url(?string $table = null): string
    {
        $query = ['utm_source' => 'qr'];

        if (filled($table)) {
            $query['table'] = $table;
        }

        // Follows the dashboard's Website address, falling back to APP_URL.
        return settings()->publicUrl().'/?'.http_build_query($query);
    }

    public function svg(?string $table = null, int $size = 1024, bool $withLogo = false): string
    {
        $builder = QrCode::format('svg')
            ->size($size)
            ->errorCorrection('H')
            ->margin(1)
            ->color(...$this->rgb($this->brand()))
            ->backgroundColor(255, 255, 255);

        $svg = $builder->generate(self::url($table));

        return $withLogo ? $this->embedLogoInSvg((string) $svg, $size) : (string) $svg;
    }

    /** @return string raw PNG bytes */
    public function png(?string $table = null, int $size = 1024, bool $withLogo = false): string
    {
        $matrix = Encoder::encode(
            self::url($table),
            ErrorCorrectionLevel::H(),
            Encoder::DEFAULT_BYTE_MODE_ECODING,
        )->getMatrix();

        $modules = $matrix->getWidth();
        $total = $modules + (2 * self::MARGIN);
        // Whole-pixel modules keep the edges crisp instead of resampled-fuzzy.
        $scale = max(1, (int) floor($size / $total));
        $canvasSize = $scale * $total;

        $manager = new ImageManager(new Driver);
        $image = $manager->create($canvasSize, $canvasSize)->fill('#ffffff');
        $brand = $this->brand();

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }
                $px = ($x + self::MARGIN) * $scale;
                $py = ($y + self::MARGIN) * $scale;
                $image->drawRectangle($px, $py, function ($rect) use ($scale, $brand) {
                    $rect->size($scale, $scale);
                    $rect->background($brand);
                });
            }
        }

        if ($withLogo && ($logo = $this->logoPath())) {
            $image = $this->overlayLogo($image, $logo, $canvasSize, $manager);
        }

        return (string) $image->toPng();
    }

    /**
     * Punch the logo into the centre on a white pad. Error-correction level H
     * tolerates roughly 30% loss, so a 22% overlay stays scannable.
     */
    private function overlayLogo($image, string $logoPath, int $canvasSize, ImageManager $manager)
    {
        $box = (int) round($canvasSize * 0.22);
        $pad = (int) round($box * 0.12);

        $image->drawRectangle(
            (int) (($canvasSize - $box - (2 * $pad)) / 2),
            (int) (($canvasSize - $box - (2 * $pad)) / 2),
            function ($rect) use ($box, $pad) {
                $rect->size($box + (2 * $pad), $box + (2 * $pad));
                $rect->background('#ffffff');
            },
        );

        $logo = $manager->read($logoPath)->scaleDown($box, $box);

        return $image->place($logo, 'center');
    }

    /** simple-qrcode's SVG backend cannot draw images, so splice one in. */
    private function embedLogoInSvg(string $svg, int $size): string
    {
        $logo = $this->logoPath();

        if (! $logo) {
            return $svg;
        }

        $box = (int) round($size * 0.22);
        $pad = (int) round($box * 0.12);
        $outer = $box + (2 * $pad);
        $offset = (int) round(($size - $outer) / 2);
        $data = base64_encode((string) file_get_contents($logo));
        $mime = str_ends_with(strtolower($logo), '.png') ? 'image/png' : 'image/webp';

        $overlay = sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" fill="#ffffff"/>'.
            '<image x="%d" y="%d" width="%d" height="%d" href="data:%s;base64,%s" '.
            'preserveAspectRatio="xMidYMid meet"/>',
            $offset, $offset, $outer, $outer,
            $offset + $pad, $offset + $pad, $box, $box, $mime, $data,
        );

        return str_replace('</svg>', $overlay.'</svg>', $svg);
    }

    private function brand(): string
    {
        $color = settings('primary_color') ?: '#3d4f2f';

        return preg_match('/^#[0-9a-f]{3,8}$/i', $color) ? $color : '#3d4f2f';
    }

    /** Absolute path to the uploaded logo, if there is one. */
    private function logoPath(): ?string
    {
        $logo = settings('logo');

        if (! $logo || ! Storage::disk('public')->exists($logo)) {
            return null;
        }

        return Storage::disk('public')->path($logo);
    }

    /** @return array{int,int,int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return [61, 79, 47];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
