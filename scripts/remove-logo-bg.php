<?php

/**
 * One-off utility: remove light gray baked-in backgrounds from brand images.
 * Source files may be JPEG with a .png extension; output is true PNG with alpha.
 */

$brandDir = __DIR__ . '/../public/images/brand';

$files = [
    'hh-logo-horizontal.png',
    'hh-logo-horizontal-white.png',
    'hh-logo-vertical.png',
    'hh-logo-vertical-white.png',
    'hh-icon.png',
    'hh-icon-white.png',
    'hh-logo.png',
    'vision-2030.png',
];

function loadImage(string $path)
{
    $bytes = file_get_contents($path, false, null, 0, 4);
    if ($bytes === false || strlen($bytes) < 4) {
        return false;
    }

    $signature = bin2hex($bytes);

    if (str_starts_with($signature, '89504e47')) {
        return imagecreatefrompng($path);
    }

    if (str_starts_with($signature, 'ffd8ff')) {
        return imagecreatefromjpeg($path);
    }

    return false;
}

foreach ($files as $file) {
    $path = $brandDir . '/' . $file;
    if (! is_file($path)) {
        echo "Skip missing: {$file}\n";
        continue;
    }

    $img = loadImage($path);
    if ($img === false) {
        echo "Failed to load: {$file}\n";
        continue;
    }

    imagesavealpha($img, true);
    imagealphablending($img, false);

    $width = imagesx($img);
    $height = imagesy($img);

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgba = imagecolorat($img, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $a = ($rgba & 0x7F000000) >> 24;

            if ($a === 127) {
                continue;
            }

            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $saturation = $max - $min;

            $isLightNeutral = $max >= 198 && $saturation <= 45;
            $isNearWhite = $r >= 228 && $g >= 228 && $b >= 228;

            if ($isLightNeutral || $isNearWhite) {
                $newAlpha = $isNearWhite ? 127 : (int) min(127, max(0, (int) round((235 - $max) * 3.2)));
                $color = imagecolorallocatealpha($img, $r, $g, $b, $newAlpha);
                imagesetpixel($img, $x, $y, $color);
            }
        }
    }

    imagepng($img, $path);
    imagedestroy($img);
    echo "Processed: {$file}\n";
}

echo "Done.\n";
