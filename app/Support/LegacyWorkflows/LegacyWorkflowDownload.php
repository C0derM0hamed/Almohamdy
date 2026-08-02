<?php

namespace App\Support\LegacyWorkflows;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class LegacyWorkflowDownload
{
    /** @param list<string> $legacyDirectories */
    public function download(string $storedPath, array $legacyDirectories): BinaryFileResponse
    {
        $normalized = ltrim(str_replace('\\', '/', trim($storedPath)), './');
        abort_if($normalized === '' || str_starts_with($normalized, '/') || in_array('..', explode('/', $normalized), true), 404);
        $basename = basename($normalized);
        $candidates = [Storage::disk('public')->path($normalized), public_path($normalized)];
        foreach ($legacyDirectories as $directory) {
            $candidates[] = base_path('../OldProject/'.trim($directory, '/').'/'.$basename);
        }
        $allowedRoots = array_filter([
            realpath(storage_path('app/public')), realpath(public_path()), realpath(base_path('../OldProject')),
        ]);
        $resolved = null;
        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_file($real) && collect($allowedRoots)->contains(fn ($root) => str_starts_with($real, $root.DIRECTORY_SEPARATOR))) {
                $resolved = $real;
                break;
            }
        }
        abort_if($resolved === null, 404);
        $response = new BinaryFileResponse($resolved, 200, ['Content-Type' => File::mimeType($resolved) ?: 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $basename, 'attachment.'.pathinfo($basename, PATHINFO_EXTENSION));

        return $response;
    }
}
