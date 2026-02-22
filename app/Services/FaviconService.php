<?php

namespace App\Services;

use App\Models\Company;

class FaviconService
{
    public function generateForCompany(?Company $company): void
    {
        $sourcePath = null;

        if ($company && is_string($company->logo) && $company->logo !== '') {
            $path = $company->logo;
            if (str_starts_with($path, '/')) {
                $path = ltrim($path, '/');
            }
            $candidate = public_path($path);
            if (is_file($candidate)) {
                $sourcePath = $candidate;
            }
        }

        if (!$sourcePath) {
            $fallback = public_path('images/jicos.png');
            $sourcePath = is_file($fallback) ? $fallback : null;
        }

        if (!$sourcePath) {
            return;
        }

        $binary = @file_get_contents($sourcePath);
        if ($binary === false) {
            return;
        }

        $png64 = $this->toPng($binary, 64, 64);
        if ($png64 === null) {
            return;
        }

        @file_put_contents(public_path('favicon.png'), $png64);
        @file_put_contents(public_path('favicon.ico'), $this->buildIcoWithPng($png64, 64, 64));
    }

    private function toPng(string $binary, int $width, int $height): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }

        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);

        ob_start();
        imagepng($dst);
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return is_string($out) ? $out : null;
    }

    private function buildIcoWithPng(string $pngBinary, int $width, int $height): string
    {
        $w = $width >= 256 ? 0 : $width;
        $h = $height >= 256 ? 0 : $height;

        $imageSize = strlen($pngBinary);
        $offset = 6 + 16;

        $header = pack('vvv', 0, 1, 1);
        $entry = pack(
            'CCCCvvVV',
            $w,
            $h,
            0,
            0,
            1,
            32,
            $imageSize,
            $offset
        );

        return $header . $entry . $pngBinary;
    }
}

