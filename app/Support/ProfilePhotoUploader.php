<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ProfilePhotoUploader
{
    private const TARGET_SIZE = 512;

    private const QUALITY = 80;

    public function store(UploadedFile $file, ?string $existingPath = null): string
    {
        $optimized = $this->optimize($file);

        if ($optimized === null) {
            throw new RuntimeException('The profile photo could not be optimized. Please upload a valid JPG, PNG, or WebP image.');
        }

        $path = sprintf('profile-photos/%s.%s', Str::uuid(), $optimized['extension']);

        if (! Storage::disk('public')->put($path, $optimized['contents'])) {
            throw new RuntimeException('The optimized profile photo could not be saved. Please try again.');
        }

        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $path;
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function optimize(UploadedFile $file): ?array
    {
        return $this->optimizeWithIntervention($file)
            ?? $this->optimizeWithGd($file);
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function optimizeWithIntervention(UploadedFile $file): ?array
    {
        if (
            ! class_exists(\Intervention\Image\ImageManager::class)
            || ! class_exists(\Intervention\Image\Drivers\Gd\Driver::class)
        ) {
            return null;
        }

        try {
            $manager = $this->makeInterventionManager();

            if ($manager === null) {
                return null;
            }

            $image = match (true) {
                method_exists($manager, 'decodePath') => $manager->decodePath($file->getRealPath()),
                method_exists($manager, 'read') => $manager->read($file->getRealPath()),
                default => null,
            };

            if ($image === null || ! method_exists($image, 'cover')) {
                return null;
            }

            $image = $image->cover(self::TARGET_SIZE, self::TARGET_SIZE);

            if ($this->supportsWebp()) {
                $webp = $this->encodeInterventionImage($image, 'webp');

                if ($webp !== null) {
                    return $webp;
                }
            }

            return $this->encodeInterventionImage($image, 'png');
        } catch (Throwable) {
            return null;
        }
    }

    private function makeInterventionManager(): ?object
    {
        $managerClass = \Intervention\Image\ImageManager::class;
        $driverClass = \Intervention\Image\Drivers\Gd\Driver::class;

        if (method_exists($managerClass, 'usingDriver')) {
            return $managerClass::usingDriver($driverClass);
        }

        return new $managerClass(new $driverClass());
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function encodeInterventionImage(object $image, string $format): ?array
    {
        try {
            $encoded = match ($format) {
                'webp' => $this->encodeInterventionWebp($image),
                'png' => $this->encodeInterventionPng($image),
                default => null,
            };

            if ($encoded === null) {
                return null;
            }

            $contents = (string) $encoded;

            if ($contents === '') {
                return null;
            }

            return [
                'extension' => $format,
                'contents' => $contents,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function encodeInterventionWebp(object $image): mixed
    {
        if (method_exists($image, 'encodeUsingFormat') && class_exists(\Intervention\Image\Format::class)) {
            return $image->encodeUsingFormat(\Intervention\Image\Format::WEBP, quality: self::QUALITY);
        }

        if (method_exists($image, 'encode') && class_exists(\Intervention\Image\Encoders\WebpEncoder::class)) {
            return $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: self::QUALITY));
        }

        return null;
    }

    private function encodeInterventionPng(object $image): mixed
    {
        if (method_exists($image, 'encodeUsingFormat') && class_exists(\Intervention\Image\Format::class)) {
            return $image->encodeUsingFormat(\Intervention\Image\Format::PNG);
        }

        if (method_exists($image, 'encode') && class_exists(\Intervention\Image\Encoders\PngEncoder::class)) {
            return $image->encode(new \Intervention\Image\Encoders\PngEncoder());
        }

        return null;
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function optimizeWithGd(UploadedFile $file): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $source = null;
        $target = null;

        try {
            $image = file_get_contents($file->getRealPath());

            if ($image === false) {
                return null;
            }

            $source = @imagecreatefromstring($image);

            if ($source === false) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $cropSize = min($width, $height);
            $sourceX = (int) (($width - $cropSize) / 2);
            $sourceY = (int) (($height - $cropSize) / 2);

            $target = imagecreatetruecolor(self::TARGET_SIZE, self::TARGET_SIZE);

            if ($target === false) {
                return null;
            }

            imagealphablending($target, false);
            imagesavealpha($target, true);

            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);

            if ($transparent !== false) {
                imagefilledrectangle($target, 0, 0, self::TARGET_SIZE, self::TARGET_SIZE, $transparent);
            }

            imagecopyresampled(
                $target,
                $source,
                0,
                0,
                $sourceX,
                $sourceY,
                self::TARGET_SIZE,
                self::TARGET_SIZE,
                $cropSize,
                $cropSize
            );

            if ($this->supportsWebp()) {
                $webp = $this->encodeGdWebp($target);

                if ($webp !== null) {
                    return $webp;
                }
            }

            return $this->encodeGdPng($target);
        } finally {
            $this->destroyGdImage($source);
            $this->destroyGdImage($target);
        }
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function encodeGdWebp(object $target): ?array
    {
        ob_start();
        $success = imagewebp($target, null, self::QUALITY);
        $contents = ob_get_clean();

        if (! $success || ! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'extension' => 'webp',
            'contents' => $contents,
        ];
    }

    /**
     * @return array{extension: string, contents: string}|null
     */
    private function encodeGdPng(object $target): ?array
    {
        if (! function_exists('imagepng')) {
            return null;
        }

        ob_start();
        $success = imagepng($target, null, 6);
        $contents = ob_get_clean();

        if (! $success || ! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'extension' => 'png',
            'contents' => $contents,
        ];
    }

    private function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }

    private function destroyGdImage(mixed $image): void
    {
        if ($image instanceof \GdImage || is_resource($image)) {
            imagedestroy($image);
        }
    }
}
