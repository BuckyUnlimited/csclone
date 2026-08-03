<?php

namespace App\Classes;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class ImageClass
{
    private FilesystemAdapter $disk;
    private string $directory;
    private int $thumbnailSize;
    private ImageManager $imageManager;

    public function __construct(string $disk = 'public', string $directory = 'default', int $thumbnailSize = 512)
    {
        $resolvedDisk = Storage::disk($disk);

        if (!$resolvedDisk instanceof FilesystemAdapter) {
            throw new \RuntimeException('Configured disk must resolve to FilesystemAdapter.');
        }

        $this->disk = $resolvedDisk;
        $this->directory = trim($directory, '/');
        $this->thumbnailSize = $thumbnailSize;
        $this->imageManager = new ImageManager(new Driver());
    }

    public function store(UploadedFile $image): string
    {
        $sourcePath = $image->getRealPath();

        if (!$sourcePath) {
            throw new \RuntimeException('Uploaded image source path is invalid.');
        }

        $extension = $image->guessExtension() ?? $image->extension() ?? 'jpg';
        $filename = Str::uuid() . '.' . $extension;
        $storedPath = $this->disk->putFileAs($this->directory, $image, $filename);

        if (!$storedPath) {
            throw new \RuntimeException('Failed to store uploaded image.');
        }

        try {
            $this->createThumbnail($sourcePath, $storedPath, $extension);
        } catch (Throwable $exception) {
            // Rollback the stored image if thumbnail creation fails
            $this->delete($storedPath);
            throw $exception;
        }

        return $storedPath;
    }

    public function delete(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        if ($this->disk->exists($imagePath)) {
            $this->disk->delete($imagePath);
        }

        $thumbnailPath = $this->thumbnailPath($imagePath);

        if ($thumbnailPath && $this->disk->exists($thumbnailPath)) {
            $this->disk->delete($thumbnailPath);
        }
    }

    public function thumbnailPath(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        $basename = basename($imagePath);

        return $this->directory . '/thumbnails/' . $basename;
    }

    public function fullUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return $this->disk->url($path);
    }

    private function createThumbnail(string $sourcePath, string $imagePath, string $extension): void
    {
        $source = $this->imageManager->decodePath($sourcePath);
        $isLandscapeOrSquare = $source->width() >= $source->height();
        $size = $this->thumbnailSize;
        $thumbnailPath = $this->thumbnailPath($imagePath);

        $thumbnail = $source->scaleDown(
            width: $isLandscapeOrSquare ? $size : null,
            height: $isLandscapeOrSquare ? null : $size
        );
        $encoded = $thumbnail->encodeUsingFileExtension($extension);

        if ($thumbnailPath) {
            $this->disk->put($thumbnailPath, (string) $encoded);
        }
    }

}
