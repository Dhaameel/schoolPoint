<?php

namespace App\Actions\Photos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorePhotoAction
{
    public function handle(UploadedFile $file, string $folder): string
    {
        $contents = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if (! $image) {
            throw ValidationException::withMessages([
                'photo' => __('messages.photo_invalid'),
            ]);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 1000;
        $scale = min(1, $max / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'schoolpoint-photo-');
        imagejpeg($canvas, $temporaryPath, 85);

        $path = $folder.'/'.Str::uuid().'.jpg';
        Storage::disk('local')->put($path, file_get_contents($temporaryPath));

        imagedestroy($image);
        imagedestroy($canvas);
        @unlink($temporaryPath);

        return $path;
    }
}
