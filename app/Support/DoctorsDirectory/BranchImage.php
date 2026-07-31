<?php

namespace App\Support\DoctorsDirectory;

final class BranchImage
{
    public static function urlForHospital(int $hospitalId): ?string
    {
        $images = config('hm.doctors_directory.branch_images', []);

        if (! is_array($images)) {
            return null;
        }

        $path = $images[$hospitalId] ?? $images[(string) $hospitalId] ?? null;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return asset(ltrim($path, '/'));
    }
}
