<?php

namespace App\Services\Pdf;

use App\Support\Inquiries\InquiryPdfFontRegistry;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DompdfPdf;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;

final class ArabicPdfService
{
    private Arabic $arabic;

    public function __construct()
    {
        $this->arabic = new Arabic();
    }

    /**
     * Render a Blade view with an embedded Arabic font and shaped text.
     *
     * Dompdf embeds TrueType fonts, but it does not perform Arabic
     * contextual shaping or bidirectional layout. Ar-PHP supplies the
     * presentation-form glyphs while the document remains UTF-8.
     */
    public function loadView(string $view, array $data = []): DompdfPdf
    {
        $html = view($view, $data)->render();
        $html = $this->prepareHtml($html);

        // Dompdf generates UFM/AFM metrics when it first sees an embedded
        // font. Never let it write those runtime artifacts into vendor/
        // (production deploys commonly make vendor read-only).
        $fontCache = storage_path('fonts');
        $tempDir = storage_path('app/dompdf-tmp');
        File::ensureDirectoryExists($fontCache);
        File::ensureDirectoryExists($tempDir);

        return Pdf::setOptions([
            'defaultFont' => 'ArabicPdf',
            'fontDir' => $fontCache,
            'fontCache' => $fontCache,
            'tempDir' => $tempDir,
            'chroot' => base_path(),
            'isRemoteEnabled' => false,
        ])->loadHTML($html, 'UTF-8');
    }

    private function prepareHtml(string $html): string
    {
        $hasArabic = preg_match('/\p{Arabic}/u', $html) === 1;
        $html = $hasArabic ? $this->shapeTextNodes($html) : $html;

        $fonts = InquiryPdfFontRegistry::fonts();
        $regular = $this->fontUrl($fonts['regular']);
        $bold = $this->fontUrl(is_file($fonts['bold']) ? $fonts['bold'] : $fonts['regular']);
        $direction = app()->getLocale() === 'ar' || $hasArabic ? 'rtl' : 'ltr';
        $textAlign = $direction === 'rtl' ? 'text-align:right;' : '';

        $style = <<<HTML
    <style id="arabic-pdf-rendering">
    @font-face {
        font-family: ArabicPdf;
        src: url("{$regular}") format("truetype");
        font-style: normal;
        font-weight: 400;
    }
    @font-face {
        font-family: ArabicPdf;
        src: url("{$bold}") format("truetype");
        font-style: normal;
        font-weight: 700;
    }
    html, body {
        font-family: ArabicPdf, sans-serif !important;
        direction: {$direction};
        {$textAlign}
    }
    </style>
    HTML;

        $headPosition = stripos($html, '</head>');

        return $headPosition === false
            ? $style.$html
            : substr($html, 0, $headPosition).$style.substr($html, $headPosition);
    }

    private function shapeTextNodes(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">'.$html,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
            );

            if ($loaded === false) {
                return $html;
            }

            $xpath = new DOMXPath($document);
            $nodes = $xpath->query(
                '//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::textarea) and not(ancestor::title) and not(ancestor::head)]',
            );

            if ($nodes !== false) {
                foreach ($nodes as $node) {
                    if (preg_match('/\p{Arabic}/u', $node->nodeValue ?? '') !== 1) {
                        continue;
                    }

                    $node->nodeValue = $this->shape($node->nodeValue, $this->shapeWidth($node));
                }
            }

            return $document->saveHTML() ?: $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
    }

    private function shapeWidth(\DOMNode $node): int
    {
        for ($current = $node->parentNode; $current; $current = $current->parentNode) {
            $name = strtolower((string) $current->nodeName);
            if (in_array($name, ['td', 'th', 'h1', 'h2', 'h3'], true)) {
                return 10000;
            }
        }

        return 50;
    }

    private function shape(?string $text, int $maxChars = 50): string
    {
        if ($text === null || $text === '') {
            return (string) $text;
        }

        set_error_handler(
            static fn (int $severity): bool => in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true),
        );

        try {
            // Keep Western digits unchanged: this is visual shaping only.
            return $this->arabic->utf8Glyphs($text, $maxChars, false);
        } finally {
            restore_error_handler();
        }
    }

    private function fontUrl(string $path): string
    {
        return 'file://'.str_replace(' ', '%20', str_replace('\\', '/', $path));
    }
}
