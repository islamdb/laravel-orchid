<?php


namespace App\Support\Traits;


use Illuminate\Support\Facades\File;

trait ResourceSortingByFilename
{
    public static function sort(): string
    {
        return collect(File::allFiles(base_path('app/Orchid/Resources')))
            ->map(function (\SplFileInfo $file) {
                return $file->getFilename();
            })->sort()
            ->values()
            ->flip()[(new \ReflectionClass(static::class))->getShortName().'.php'] + 2000;
    }
}
