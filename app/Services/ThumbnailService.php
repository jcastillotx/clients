<?php

namespace App\Services;

class ThumbnailService
{
    /**
     * Create a JPEG thumbnail (binary) from an image file.
     * Returns null if thumbnail generation isn't available.
     */
    public function makeJpegThumbnailFromFile(string $absolutePath, int $maxWidth = 640): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        // Prefer GD if available (common on shared hosting). If not, skip gracefully.
        if (!extension_loaded('gd') || !function_exists('imagecreatefromstring')) {
            return null;
        }

        $data = @file_get_contents($absolutePath);
        if ($data === false) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if (!$src) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($src);
            return null;
        }

        $dstW = min($maxWidth, $srcW);
        $dstH = (int) round($srcH * ($dstW / $srcW));
        $dstH = max(1, $dstH);

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            imagedestroy($src);
            return null;
        }

        // White background (avoid black when converting transparent PNGs to JPG)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        ob_start();
        imagejpeg($dst, null, 82);
        $jpeg = ob_get_clean();

        imagedestroy($dst);
        imagedestroy($src);

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }
}

