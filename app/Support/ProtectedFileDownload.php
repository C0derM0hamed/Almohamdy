<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ProtectedFileDownload
{
    /**
     * @param  list<string>  $legacyDirectories
     */
    public function download(?string $storedPath, ?string $displayName = null, array $legacyDirectories = []): BinaryFileResponse
    {
        $resolvedPath = $this->resolve($storedPath, $legacyDirectories);

        abort_if($resolvedPath === null, 404);

        $filename = $this->filename($displayName, $storedPath, $resolvedPath);
        $mime = File::mimeType($resolvedPath) ?: 'application/octet-stream';

        $response = new BinaryFileResponse($resolvedPath, 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $this->asciiFallbackName($filename)
        );

        return $response;
    }

    /**
     * @param  list<string>  $legacyDirectories
     */
    private function resolve(?string $storedPath, array $legacyDirectories): ?string
    {
        $normalized = $this->normalize($storedPath);

        if ($normalized === null) {
            return null;
        }

        $candidates = [
            Storage::disk('public')->path($normalized),
            public_path($normalized),
        ];

        $basename = basename($normalized);
        foreach ($legacyDirectories as $directory) {
            $safeDirectory = trim(str_replace('\\', '/', $directory), '/');
            if ($safeDirectory !== '') {
                $candidates[] = public_path($safeDirectory.'/'.$basename);
            }
        }

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);

            if ($resolved !== false && is_file($resolved) && $this->isAllowedRoot($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private function normalize(?string $storedPath): ?string
    {
        $path = trim((string) $storedPath);

        if ($path === ''
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), './');
        $segments = array_values(array_filter(explode('/', $path), static fn ($segment) => $segment !== ''));

        if ($segments === [] || in_array('..', $segments, true)) {
            return null;
        }

        return implode('/', $segments);
    }

    private function isAllowedRoot(string $resolvedPath): bool
    {
        foreach ([public_path(), storage_path('app/public')] as $root) {
            $resolvedRoot = realpath($root);

            if ($resolvedRoot !== false && str_starts_with($resolvedPath, $resolvedRoot.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function filename(?string $displayName, ?string $storedPath, string $resolvedPath): string
    {
        $filename = trim((string) $displayName);
        $extension = pathinfo($resolvedPath, PATHINFO_EXTENSION);

        if ($filename === '') {
            $filename = basename((string) $storedPath) ?: basename($resolvedPath);
        }

        if ($extension !== '' && pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.'.$extension;
        }

        return str_replace(["\r", "\n", '/', '\\'], '', $filename) ?: 'attachment';
    }

    private function asciiFallbackName(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $fallback = 'attachment';

        if ($extension !== '') {
            $fallback .= '.'.preg_replace('/[^A-Za-z0-9]+/', '', $extension);
        }

        return $fallback;
    }
}
