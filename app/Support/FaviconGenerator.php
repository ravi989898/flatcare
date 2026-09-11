<?php

namespace App\Support;

/**
 * Renders the platform's browser-tab icon into every static file the app
 * actually links to (public/favicon.ico + the PNG/apple-touch variants
 * referenced from resources/views/welcome.blade.php). Regenerating these in
 * place — rather than serving them from a dynamic route — means every page
 * that already points at /favicon.ico (including the AdminLTE-rendered
 * admin panel and auth pages, which fall back to the browser's automatic
 * favicon request) picks up a super-admin-uploaded icon with no template
 * changes, the same way partials/brand.blade.php already does for the logo.
 */
class FaviconGenerator
{
    /**
     * @var array<string, int> Output filename (relative to public/) => square size in px.
     */
    private const PNG_SIZES = [
        'favicon-16x16.png' => 16,
        'favicon-32x32.png' => 32,
        'favicon-192.png' => 192,
    ];

    private const ICO_SIZES = [16, 32, 48];

    private const APPLE_TOUCH_SIZE = 180;

    /**
     * Build public/favicon.ico + the PNG/apple-touch-icon variants from a
     * source image on disk (any size, need not be square — it's
     * center-cropped first).
     */
    public static function generate(string $sourceAbsolutePath, string $publicPath): void
    {
        $source = self::load($sourceAbsolutePath);
        $square = self::squareCrop($source);
        imagedestroy($source);

        foreach (self::PNG_SIZES as $filename => $size) {
            $resized = self::resize($square, $size);
            imagepng($resized, $publicPath . '/' . $filename);
            imagedestroy($resized);
        }

        // Apple touch icons render on a solid background on iOS home
        // screens, so a transparent source is flattened onto white first.
        $appleCanvas = self::resize(self::flatten($square, 255, 255, 255), self::APPLE_TOUCH_SIZE);
        imagepng($appleCanvas, $publicPath . '/apple-touch-icon.png');
        imagedestroy($appleCanvas);

        $icoImages = [];
        foreach (self::ICO_SIZES as $size) {
            $icoImages[$size] = self::resize($square, $size);
        }
        file_put_contents($publicPath . '/favicon.ico', self::encodeIco($icoImages));
        foreach ($icoImages as $img) {
            imagedestroy($img);
        }

        imagedestroy($square);
    }

    /** @return \GdImage */
    private static function load(string $path)
    {
        $data = file_get_contents($path);
        $image = imagecreatefromstring($data);

        if ($image === false) {
            throw new \RuntimeException("Could not read image at {$path}");
        }

        imagesavealpha($image, true);

        return $image;
    }

    /** @param \GdImage $image @return \GdImage */
    private static function squareCrop($image)
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $side = min($w, $h);

        $square = imagecreatetruecolor($side, $side);
        imagesavealpha($square, true);
        imagealphablending($square, false);
        $transparent = imagecolorallocatealpha($square, 0, 0, 0, 127);
        imagefill($square, 0, 0, $transparent);
        imagealphablending($square, true);

        imagecopy($square, $image, 0, 0, (int) (($w - $side) / 2), (int) (($h - $side) / 2), $side, $side);

        return $square;
    }

    /** @param \GdImage $image @return \GdImage */
    private static function resize($image, int $size)
    {
        $resized = imagecreatetruecolor($size, $size);
        imagesavealpha($resized, true);
        imagealphablending($resized, false);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
        imagealphablending($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));

        return $resized;
    }

    /** @param \GdImage $image @return \GdImage */
    private static function flatten($image, int $r, int $g, int $b)
    {
        $size = imagesx($image);
        $canvas = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($canvas, $r, $g, $b);
        imagefill($canvas, 0, 0, $bg);
        imagecopy($canvas, $image, 0, 0, 0, 0, $size, $size);

        return $canvas;
    }

    /**
     * Packs GD images into a modern PNG-in-ICO container (supported since
     * Windows Vista, and by every current browser) — far simpler than
     * encoding legacy BMP frames.
     *
     * @param array<int, \GdImage> $imagesBySize
     */
    private static function encodeIco(array $imagesBySize): string
    {
        $pngBlobs = [];
        foreach ($imagesBySize as $size => $image) {
            ob_start();
            imagepng($image);
            $pngBlobs[$size] = ob_get_clean();
        }

        $count = count($pngBlobs);
        $header = pack('vvv', 0, 1, $count);

        $entries = '';
        $offset = 6 + ($count * 16);
        $data = '';
        foreach ($pngBlobs as $size => $blob) {
            $dim = $size >= 256 ? 0 : $size;
            $entries .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, strlen($blob), $offset);
            $data .= $blob;
            $offset += strlen($blob);
        }

        return $header . $entries . $data;
    }
}
