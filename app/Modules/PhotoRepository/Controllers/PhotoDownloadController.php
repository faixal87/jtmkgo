<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaDownloadLog;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoDownloadController extends Controller
{
    public function __invoke(Request $request, MediaPhoto $mediaPhoto): StreamedResponse
    {
        $mediaPhoto->loadMissing(['profile', 'category']);

        Gate::authorize('download', $mediaPhoto);

        abort_unless(Storage::disk('public')->exists($mediaPhoto->photo_path), 404);

        $format = $this->requestedFormat($request);

        if ($format === 'webp' && strtolower(pathinfo($mediaPhoto->photo_path, PATHINFO_EXTENSION)) === 'webp') {
            $this->recordDownload($request, $mediaPhoto);

            return Storage::disk('public')->download(
                $mediaPhoto->photo_path,
                $mediaPhoto->downloadFilename('webp'),
                ['Content-Type' => 'image/webp']
            );
        }

        $contents = Storage::disk('public')->get($mediaPhoto->photo_path);
        $downloadContents = $format === 'webp'
            ? $this->convertImage($contents, 'webp')
            : $this->convertImage($contents, 'jpg');

        $this->recordDownload($request, $mediaPhoto);

        return response()->streamDownload(
            fn () => print $downloadContents,
            $mediaPhoto->downloadFilename($format),
            ['Content-Type' => $format === 'webp' ? 'image/webp' : 'image/jpeg']
        );
    }

    private function requestedFormat(Request $request): string
    {
        $format = $request->query('format', 'jpg');
        $format = is_string($format) ? strtolower($format) : 'jpg';

        return in_array($format, ['jpg', 'webp'], true) ? $format : 'jpg';
    }

    private function recordDownload(Request $request, MediaPhoto $mediaPhoto): void
    {
        $mediaPhoto->increment('download_count');

        MediaDownloadLog::create([
            'media_photo_id' => $mediaPhoto->id,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);
    }

    private function convertImage(string $contents, string $format): string
    {
        abort_unless(function_exists('imagecreatefromstring'), 500, 'Image conversion requires PHP GD support.');
        abort_unless($format !== 'jpg' || function_exists('imagejpeg'), 500, 'JPG export requires PHP GD JPEG support.');

        $source = @imagecreatefromstring($contents);

        abort_unless($source !== false, 422, 'The stored photo could not be converted.');

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $canvas = imagecreatetruecolor($width, $height);

            abort_unless($canvas !== false, 500, 'The export canvas could not be created.');

            try {
                if ($format === 'jpg') {
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
                } else {
                    abort_unless(function_exists('imagewebp'), 500, 'WEBP export requires PHP GD WebP support.');

                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                    imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
                }

                imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

                ob_start();
                $success = $format === 'webp'
                    ? imagewebp($canvas, null, 88)
                    : imagejpeg($canvas, null, 92);
                $converted = ob_get_clean();

                abort_unless($success && is_string($converted) && $converted !== '', 500, 'The photo could not be exported.');

                return $converted;
            } finally {
                imagedestroy($canvas);
            }
        } finally {
            imagedestroy($source);
        }
    }
}
