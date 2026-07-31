<?php

/**
 * Remove "Dashboard" breadcrumb entries from Blade views (UI-only cleanup).
 */

$root = dirname(__DIR__);
$viewsDir = $root.'/resources/views';

$patterns = [
    // Legacy <li> breadcrumb item
    '/\s*<li class="hm-breadcrumb__item">\s*<a href="\{\{ route\(\$homeRoute \?\? \'dashboard\'\) \}\}">\{\{ __\(\'dashboard\.title\'\) \}\}<\/a>\s*<\/li>\s*/s' => '',

    // Breadcrumb array item in @include partials
    '/\s*\[\s*\'label\'\s*=>\s*__\(\'dashboard\.title\'\),\s*\'url\'\s*=>\s*route\(\$homeRoute \?\? \'dashboard\'\),\s*\],?\s*/s' => '',

    // Inline wan / dd breadcrumb link + separator
    '/\s*<a href="\{\{ route\(\$homeRoute \?\? \'dashboard\'\) \}\}">\{\{ __\(\'dashboard\.title\'\) \}\}<\/a>\s*<span class="(?:wan-breadcrumb__sep|dd-breadcrumb-sep)"[^>]*>[\s\S]*?<\/span>\s*/s' => '',

    // Bootstrap breadcrumb in placeholder
    '/\s*<li class="breadcrumb-item"><a href="\{\{ route\(\$homeRoute \?\? \'dashboard\'\) \}\}">\{\{ __\(\'dashboard\.title\'\) \}\}<\/a><\/li>\s*/s' => '',

    // Footer "back to dashboard" button blocks
    '/\s*<a href="\{\{ route\(\$homeRoute \?\? \'dashboard\'\) \}\}" class="btn hm-btn hm-btn--light">[\s\S]*?\{\{ __\(\'dashboard\.title\'\) \}\}[\s\S]*?<\/a>\s*/s' => '',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS)
);

$changed = [];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();

    // Keep layout title default and sidebar home link
    if (str_contains($path, 'layouts/app.blade.php')
        || str_contains($path, 'layouts/partials/sidebar.blade.php')
        || str_contains($path, 'layouts/partials/navbar.blade.php')
        || str_contains($path, 'dashboard/home.blade.php')) {
        continue;
    }

    $original = file_get_contents($path);
    $updated = $original;

    foreach ($patterns as $pattern => $replacement) {
        $updated = preg_replace($pattern, $replacement, $updated);
    }

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        $changed[] = str_replace($root.'/', '', $path);
    }
}

echo 'Updated '.count($changed)." view(s):\n";

foreach ($changed as $file) {
    echo " - {$file}\n";
}
