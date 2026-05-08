<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoUploader
{
    public function store(UploadedFile $file, ?string $existingPath = null): string
    {
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        $path = 'profile-photos/'.Str::uuid().'.jpg';
        $image = file_get_contents($file->getRealPath());

        if ($image !== false && function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $resource = imagecreatefromstring($image);

            if ($resource !== false) {
                $width = imagesx($resource);
                $height = imagesy($resource);
                $size = min($width, $height);
                $sourceX = (int) (($width - $size) / 2);
                $sourceY = (int) (($height - $size) / 2);
                $targetSize = 512;
                $target = imagecreatetruecolor($targetSize, $targetSize);

                imagecopyresampled($target, $resource, 0, 0, $sourceX, $sourceY, $targetSize, $targetSize, $size, $size);

                ob_start();
                imagejpeg($target, null, 85);
                $compressed = ob_get_clean();

                imagedestroy($resource);
                imagedestroy($target);

                if ($compressed !== false) {
                    Storage::disk('public')->put($path, $compressed);

                    return $path;
                }
            }
        }

        return $file->store('profile-photos', 'public');
    }
}
