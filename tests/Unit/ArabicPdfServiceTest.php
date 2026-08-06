<?php

namespace Tests\Unit;

use App\Services\Pdf\ArabicPdfService;
use Tests\TestCase;

class ArabicPdfServiceTest extends TestCase
{
    public function test_arabic_pdf_embeds_fonts_and_renders_a_mixed_rtl_document(): void
    {
        app()->setLocale('ar');

        $record = (object) [
            'id' => 77,
            'patient_name' => 'محمد علي - Mixed English ABC',
            'file_number' => 'AR-123',
            'nationality_name_ar' => 'سعودي',
            'room_name_ar' => 'غرفة التنويم',
            'doctor' => 'طبيب أحمد',
            'days' => 3,
            'procedurs' => 'إجراء طبي',
            'discount' => '10',
            'tools_value' => '25',
        ];

        $pdf = app(ArabicPdfService::class)
            ->loadView('admission-calculator.pdf', ['record' => $record, 'type' => 'standard'])
            ->setPaper('a4')
            ->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('NotoKufiArabic-Regular', $pdf);
        $this->assertStringContainsString('NotoKufiArabic-Bold', $pdf);
    }
}
