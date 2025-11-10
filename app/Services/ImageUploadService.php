<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class ImageUploadService
{
    private const DISK = 'public';
    private const PATH = 'contacts';

    public function upload(UploadedFile $file): string
    {
        $filename = $this->generateFilename($file);
        $path = Storage::disk(self::DISK)->putFileAs(self::PATH, $file, $filename);

        return $path;
    }

    public function delete(?string $imagePath): void
    {
        if ($imagePath && Storage::disk(self::DISK)->exists($imagePath)) {
            Storage::disk(self::DISK)->delete($imagePath);
        }
    }

    private function generateFilename(UploadedFile $file): string
    {
        $hash = md5(microtime());
        $extension = $file->getClientOriginalExtension();

        return "{$hash}.{$extension}";
    }
}
