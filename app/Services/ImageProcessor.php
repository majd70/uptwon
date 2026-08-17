<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Every image that reaches the public disk goes through here: capped at
 * MAX_EDGE on its long side and re-encoded as WebP.
 */
class ImageProcessor
{
    public const MAX_EDGE = 1200;

    /**
     * Menu rows show a 76px square, so a 1200px file there is ~5x more bytes
     * than the page can use. Every stored image gets a list-sized companion.
     */
    public const THUMB_EDGE = 260;

    public const THUMB_SUFFIX = '-thumb';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Store raw image bytes on the public disk as WebP.
     *
     * @param  string  $directory  e.g. "menu-items"
     * @param  string  $basename   used to build a stable, readable file name
     * @return string  the path relative to the public disk
     */
    public function storeFromContents(string $contents, string $directory, string $basename): string
    {
        $image = $this->manager->read($contents);

        if (max($image->width(), $image->height()) > self::MAX_EDGE) {
            $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);
        }

        $path = trim($directory, '/').'/'.$this->fileName($basename);
        Storage::disk('public')->put($path, (string) $image->toWebp(quality: 82));

        // Square list thumbnail, cropped to fill so every row lines up.
        $thumb = $this->manager->read($contents)->coverDown(self::THUMB_EDGE, self::THUMB_EDGE);
        Storage::disk('public')->put(
            self::thumbPath($path),
            (string) $thumb->toWebp(quality: 78),
        );

        return $path;
    }

    /** Convention: foo.webp -> foo-thumb.webp. */
    public static function thumbPath(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $base = substr($path, 0, -(strlen($ext) + 1));

        return $base.self::THUMB_SUFFIX.'.'.$ext;
    }

    public function storeFromPath(string $absolutePath, string $directory, ?string $basename = null): string
    {
        return $this->storeFromContents(
            file_get_contents($absolutePath),
            $directory,
            $basename ?? pathinfo($absolutePath, PATHINFO_FILENAME),
        );
    }

    /**
     * Callback for Filament's FileUpload::saveUploadedFileUsing(), so admin
     * uploads get the same 1200px / WebP treatment as imported images.
     */
    public static function handleUpload(TemporaryUploadedFile $file, string $directory): string
    {
        return app(self::class)->storeFromContents(
            $file->get(),
            $directory,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'-'.Str::random(6),
        );
    }

    /**
     * Deterministic name so re-importing the same item overwrites its image
     * instead of piling up copies.
     */
    private function fileName(string $basename): string
    {
        $slug = Str::slug($basename) ?: 'image';

        return "{$slug}.webp";
    }
}
