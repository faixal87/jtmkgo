<?php

namespace App\Modules\PhotoRepository\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PhotoProcessingService
{
    private const FINAL_MAX_EDGE = 1600;
    private const THUMBNAIL_MAX_EDGE = 400;
    private const MAX_FINAL_BYTES = 5_242_880;
    private const QUALITY_STEPS = [84, 80, 72, 64, 56];
    private const EDGE_STEPS = [1600, 1400, 1200, 1000, 800];

    /**
     * @return array{
     *     photo_path: string,
     *     thumbnail_path: string,
     *     original_filename: string|null,
     *     file_size: int,
     *     mime_type: string,
     *     width: int,
     *     height: int
     * }
     */
    public function process(UploadedFile $file): array
    {
        $source = $this->createSourceImage($file);

        try {
            $final = $this->createOptimizedImage($source, self::FINAL_MAX_EDGE, self::MAX_FINAL_BYTES);
            $thumbnail = $this->createOptimizedImage($source, self::THUMBNAIL_MAX_EDGE, null);
        } finally {
            $this->destroy($source);
        }

        $basePath = 'photo-repository/'.now()->format('Y/m').'/'.Str::uuid();
        $photoPath = "{$basePath}.{$final['extension']}";
        $thumbnailPath = "{$basePath}-thumb.{$thumbnail['extension']}";

        if (! Storage::disk('public')->put($photoPath, $final['contents'])) {
            throw new RuntimeException('The optimized photo could not be saved. Please try again.');
        }

        if (! Storage::disk('public')->put($thumbnailPath, $thumbnail['contents'])) {
            Storage::disk('public')->delete($photoPath);

            throw new RuntimeException('The optimized thumbnail could not be saved. Please try again.');
        }

        return [
            'photo_path' => $photoPath,
            'thumbnail_path' => $thumbnailPath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => strlen($final['contents']),
            'mime_type' => $final['mime_type'],
            'width' => $final['width'],
            'height' => $final['height'],
        ];
    }

    /**
     * @return array{extension: string, mime_type: string, contents: string, width: int, height: int}
     */
    private function createOptimizedImage(object $source, int $maxEdge, ?int $maxBytes): array
    {
        $best = null;
        $edgeSteps = $maxEdge === self::FINAL_MAX_EDGE ? self::EDGE_STEPS : [$maxEdge];

        foreach ($edgeSteps as $edge) {
            $target = $this->resizeWithinMaxEdge($source, min($edge, $maxEdge));

            try {
                $encoded = $this->encodePreferred($target);
            } finally {
                $this->destroy($target);
            }

            foreach ($encoded as $candidate) {
                $best = $candidate;

                if ($maxBytes === null || strlen($candidate['contents']) <= $maxBytes) {
                    return $candidate;
                }
            }
        }

        if ($best === null) {
            throw new RuntimeException('The uploaded image could not be optimized. Please upload a valid JPG, PNG, or WebP image.');
        }

        return $best;
    }

    /**
     * @return list<array{extension: string, mime_type: string, contents: string, width: int, height: int}>
     */
    private function encodePreferred(object $image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $candidates = [];

        if (function_exists('imagewebp')) {
            foreach (self::QUALITY_STEPS as $quality) {
                $contents = $this->capture(fn () => imagewebp($image, null, $quality));

                if ($contents !== null) {
                    $candidates[] = [
                        'extension' => 'webp',
                        'mime_type' => 'image/webp',
                        'contents' => $contents,
                        'width' => $width,
                        'height' => $height,
                    ];
                }
            }

            if ($candidates !== []) {
                return $candidates;
            }
        }

        if (! function_exists('imagepng')) {
            throw new RuntimeException('Image optimization requires GD WebP or PNG support.');
        }

        $contents = $this->capture(fn () => imagepng($image, null, 7));

        if ($contents === null) {
            throw new RuntimeException('The uploaded image could not be encoded.');
        }

        return [[
            'extension' => 'png',
            'mime_type' => 'image/png',
            'contents' => $contents,
            'width' => $width,
            'height' => $height,
        ]];
    }

    private function createSourceImage(UploadedFile $file): object
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Image optimization requires the PHP GD extension.');
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Please upload a valid JPG, PNG, or WebP image.');
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($source);
        }

        imagealphablending($source, true);
        imagesavealpha($source, true);

        return $source;
    }

    private function resizeWithinMaxEdge(object $source, int $maxEdge): object
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $maxEdge / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($target === false) {
            throw new RuntimeException('The uploaded image could not be resized.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);

        if ($transparent !== false) {
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        return $target;
    }

    private function capture(callable $encoder): ?string
    {
        ob_start();
        $success = $encoder();
        $contents = ob_get_clean();

        if (! $success || ! is_string($contents) || $contents === '') {
            return null;
        }

        return $contents;
    }

    private function destroy(mixed $image): void
    {
        if ($image instanceof \GdImage || is_resource($image)) {
            imagedestroy($image);
        }
    }
}
