<?php
namespace App\MediaLibrary;

use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DateFolderPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $dateFolder = now()->format('d-m-Y'); // e.g., 09-11-2025
        return "uploads/{$dateFolder}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive/';
    }
}

