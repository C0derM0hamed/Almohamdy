<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->translate('lawsuit_status', [
            'جديد' => 'New',
            'تم الرفع بناجز' => 'Filed in Najiz',
            'منظورة' => 'Under Review',
            'الجلسات' => 'Hearings',
            'مطلوب استكمال مستندات' => 'Documents Required',
            'تم الصلح' => 'Settlement Reached',
            'حكم لصالح المستشفى' => 'Judgment for the Hospital',
            'حكم برد الدعوى' => 'Claim Dismissed',
            'مشطوبة' => 'Struck Off',
            'قيد الإستئناف / إلتماس' => 'Under Appeal / Reconsideration',
            'إحالة القضية للشؤون الصحية' => 'Referred to Health Affairs',
            'تم تنفيذ الحكم' => 'Judgment Enforced',
            'تم التنازل' => 'Waived',
            'تم السداد' => 'Paid',
            'تم إستكمال المستندات' => 'Documents Completed',
        ]);

        $this->translate('lawsuit_payment_type', [
            'نقدي' => 'Cash',
            'وزارة الصحة' => 'Ministry of Health',
            'شركة التأمين' => 'Insurance Company',
        ]);

        $this->translate('lawsuit_approval_status', [
            'تمت الموافقة' => 'Approved',
            'إعادة الطلب' => 'Resubmission Required',
            'تم الرفض' => 'Rejected',
        ]);
    }

    public function down(): void
    {
        // This is a corrective data migration. Keep manual English edits intact on rollback.
    }

    /** @param array<string, string> $translations */
    private function translate(string $table, array $translations): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'name_ar')
            || ! Schema::hasColumn($table, 'name_en')) {
            return;
        }

        foreach ($translations as $arabic => $english) {
            DB::table($table)
                ->where('name_ar', $arabic)
                ->where(static function ($query) use ($arabic): void {
                    $query->whereNull('name_en')
                        ->orWhere('name_en', '')
                        ->orWhere('name_en', $arabic);
                })
                ->update(['name_en' => $english]);
        }
    }
};
