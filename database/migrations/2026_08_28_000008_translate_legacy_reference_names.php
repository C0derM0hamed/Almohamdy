<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'complaints_status', 'complaint_closing_reasons', 'complaint_letter_receiver',
        'duty_period', 'country', 'branches', 'branches_departments', 'branches_area',
        'branches_service_type', 'notice_type', 'inquiries', 'user_groups', 'job_titles',
        'governmental_services_type', 'post_type', 'page', 'booking_period',
        'ehala_case_apology_type', 'admission_rooms', 'admission_nationality',
    ];

    public function up(): void
    {
        // Some legacy reference columns are only 50–150 characters wide,
        // while the translated catalog contains descriptive labels. Widen
        // the English columns before backfilling them so an older schema
        // cannot abort the deployment halfway through this migration.
        $this->widenEnglishColumns();

        $catalog = require base_path('lang/en/reference_data.php');
        $catalog = array_merge($catalog, [
            'تم إرسال الشكوى' => 'Complaint sent',
            'تم إرسال بريد تذكيرى اول' => 'First reminder email sent',
            'تم إرسال بريد تذكيرى ثاني' => 'Second reminder email sent',
            'تم تصعيد الشكوى لعدم رد القسم المعني' => 'Complaint escalated because the responsible department did not respond',
            'تم معالجة الشكوى' => 'Complaint processed',
            'تم إغلاق الشكوى' => 'Complaint closed',
            'الصباحية' => 'Morning shift',
            'المسائية' => 'Evening shift',
            'الليلية' => 'Night shift',
            'مصر' => 'Egypt',
            'السعودية' => 'Saudi Arabia',
            'الامارات' => 'United Arab Emirates',
            'الإمارات' => 'United Arab Emirates',
            'سوريا' => 'Syria',
            'الهند' => 'India',
            'تونس' => 'Tunisia',
            'الاردن' => 'Jordan',
            'الأردن' => 'Jordan',
            'مصري - بريطاني' => 'Egyptian - British',
        ]);

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'name_ar')
                || ! Schema::hasColumn($table, 'name_en')) {
                continue;
            }

            DB::table($table)
                ->select(['id', 'name_ar', 'name_en'])
                ->where(function ($query): void {
                    $query->whereNull('name_en')->orWhere('name_en', '')->orWhereColumn('name_en', 'name_ar');
                })
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $catalog): void {
                    foreach ($rows as $row) {
                        $arabic = $this->normalize($row->name_ar);
                        $english = $catalog[$arabic] ?? null;

                        if (is_string($english) && $english !== '' && $english !== $arabic) {
                            DB::table($table)->where('id', $row->id)->update(['name_en' => $english]);
                        }
                    }
                });
        }

        // The referral country table uses a longer column name than the
        // other legacy reference tables.
        if (Schema::hasTable('countries')
            && Schema::hasColumn('countries', 'country_nationality_ar')
            && Schema::hasColumn('countries', 'country_nationality_en')) {
            DB::table('countries')
                ->select(['id', 'country_nationality_ar', 'country_nationality_en'])
                ->where(function ($query): void {
                    $query->whereNull('country_nationality_en')
                        ->orWhere('country_nationality_en', '')
                        ->orWhereColumn('country_nationality_en', 'country_nationality_ar');
                })
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($catalog): void {
                    foreach ($rows as $row) {
                        $arabic = $this->normalize($row->country_nationality_ar);
                        $english = $catalog[$arabic] ?? null;

                        if (is_string($english) && $english !== '' && $english !== $arabic) {
                            DB::table('countries')->where('id', $row->id)->update(['country_nationality_en' => $english]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Preserve translated values and any later manual corrections.
    }

    private function normalize(?string $value): string
    {
        return trim((string) (preg_replace('/\s+/u', ' ', (string) $value) ?? $value));
    }

    private function widenEnglishColumns(): void
    {
        foreach ($this->tables as $table) {
            $this->widenColumn($table, 'name_en');
        }

        $this->widenColumn('countries', 'country_nationality_en');
    }

    private function widenColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $definition = DB::selectOne(
            'select data_type, character_maximum_length, is_nullable, column_default, character_set_name, collation_name
             from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column],
        );

        if ($definition === null
            || strtolower((string) $definition->data_type) !== 'varchar'
            || (int) $definition->character_maximum_length >= 255) {
            return;
        }

        $nullable = strtoupper((string) $definition->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';
        $charset = $this->safeIdentifier((string) ($definition->character_set_name ?? ''));
        $collation = $this->safeIdentifier((string) ($definition->collation_name ?? ''));
        $default = $definition->column_default === null
            ? ''
            : " DEFAULT '".str_replace("'", "''", (string) $definition->column_default)."'";

        DB::statement(sprintf(
            'alter table `%s` modify `%s` varchar(255)%s%s%s%s',
            $table,
            $column,
            $charset !== '' ? ' character set '.$charset : '',
            $collation !== '' ? ' collate '.$collation : '',
            ' '.$nullable,
            $default,
        ));
    }

    private function safeIdentifier(string $value): string
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $value) === 1 ? $value : '';
    }
};
