<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class FaviconController extends Controller
{
    public function show(Request $request)
    {
        $company = Company::query()->select(['id', 'logo', 'updated_at'])->first();

        $sourcePath = null;
        if ($company && is_string($company->logo) && $company->logo !== '') {
            if (str_starts_with($company->logo, '/')) {
                $sourcePath = public_path(ltrim($company->logo, '/'));
            } else {
                $sourcePath = public_path($company->logo);
            }
        }

        if (!$sourcePath || !is_file($sourcePath)) {
            $fallback = public_path('images/jicos.png');
            $sourcePath = is_file($fallback) ? $fallback : public_path('favicon.ico');
        }

        $binary = @file_get_contents($sourcePath);
        if ($binary === false) {
            return response('', 404);
        }

        $etag = 'W/"' . sha1($binary) . '"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache');
        }

        $png = $this->toPng($binary, 64, 64);
        if ($png === null) {
            $contentType = $this->guessContentType($sourcePath);

            return response($binary, 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('ETag', $etag);
        }

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('ETag', $etag);
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

    private function guessContentType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };
    }
}
