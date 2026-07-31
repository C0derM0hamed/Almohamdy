<?php

namespace App\Services\Inquiries;

use App\Models\InquiryAndService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class InquiryPdfService
{
    public function __construct(
        private readonly InquiryAndServiceService $inquiryService,
        private readonly InquiryTimelineService $timelineService,
    ) {}

    public function download(InquiryAndService $inquiry, string $direction): Response
    {
        $timeline = $this->timelineService->build($inquiry);
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';
        $generatedAt = now()->format('Y-m-d H:i');

        // xbriyaz ships with mPDF and supports Arabic OTL safely.
        // Noto Kufi + useOTL crashes mPDF (Undefined array key 0 in TTFontFile).
        $fontFamily = 'xbriyaz';

        $html = view('inquiries.pdf.report', [
            'inquiry' => $inquiry,
            'timeline' => $timeline,
            'statusLabel' => $this->inquiryService->statusLabel($inquiry),
            'departmentLabel' => $inquiry->inquiredSection?->legacyNavName() ?? '—',
            'inquiryTypeLabel' => $inquiry->inquiryType?->localizedName() ?? '—',
            'detailsLabel' => trim((string) $inquiry->inquiry_details) ?: '—',
            'createdByLabel' => $inquiry->creatorDisplayName(),
            'senderBranchLabel' => $inquiry->senderBranch?->localizedName() ?? '—',
            'enquirerLabel' => $inquiry->enquirerDisplayName(),
            'pdfReference' => __('inquiries.pdf.reference', [
                'id' => $inquiry->id,
                'date' => $generatedAt,
            ]),
            'isRtl' => $isRtl,
            'locale' => $locale,
            'fontFamily' => $fontFamily,
        ])->render();

        $mpdf = $this->createMpdf($isRtl, $fontFamily);
        $mpdf->WriteHTML($html);

        $filename = sprintf(
            'inquiry-%d-%s.pdf',
            $inquiry->id,
            now()->format('Ymd')
        );

        return response($mpdf->Output($filename, Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function createMpdf(bool $isRtl, string $fontFamily): Mpdf
    {
        $tempDir = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDir);

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'default_font' => $fontFamily,
            'directionality' => $isRtl ? 'rtl' : 'ltr',
            'fontDir' => $defaultConfig['fontDir'],
            'fontdata' => $defaultFontConfig['fontdata'],
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
    }
}
