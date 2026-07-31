<?php

namespace App\Support\Inquiries;

class InquiryPdfFontRegistry
{
    /**
     * @return array{regular: string, bold: string, directory: string}
     */
    public static function fonts(): array
    {
        $publicBase = public_path('fonts/noto-kufi-arabic');
        $storageBase = storage_path('fonts/noto-kufi-arabic');

        $directory = is_file($publicBase.'/NotoKufiArabic-Regular.ttf')
            ? $publicBase
            : $storageBase;

        return [
            'directory' => self::normalizePath($directory),
            'regular' => self::normalizePath($directory.'/NotoKufiArabic-Regular.ttf'),
            'bold' => self::normalizePath($directory.'/NotoKufiArabic-Bold.ttf'),
        ];
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
