<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The local database contains a partial, non-destructive import of these
     * tables.  The legacy dump is the source of truth for the inspection
     * workflow, so restore the missing columns in place instead of replacing
     * any table or row.
     */
    public function up(): void
    {
        $this->addColumn('government_inspection_visits', 'sms_tocken', 'string', [64]);
        $this->addColumn('government_inspection_visits', 'reply', 'integer', [], 0);
        $this->addColumn('government_inspection_visits', 'affidavit_document', 'string', [100]);

        $this->addColumn('government_inspection_visits_numbers', 'token', 'string', [64]);
        $this->addColumn('government_inspection_visits_numbers', 'created_at', 'dateTime');
        $this->addColumn('government_inspection_visits_numbers', 'created_by', 'integer');
        $this->addColumn('government_inspection_visits_numbers', 'status', 'integer', [], 0);

        $this->addColumn('government_inspection_visits_abuses_and_notes', 'date', 'dateTime');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'type', 'integer');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'abuse_note_title', 'text');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'reply', 'text');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'uploaded_file', 'string', [255]);
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'replied_at', 'dateTime');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'replied_by', 'integer');
        $this->addColumn('government_inspection_visits_abuses_and_notes', 'created_by_type', 'integer', [], 1);

        $this->addColumn('government_inspection_visits_returned', 'reason', 'text');
        $this->addColumn('government_inspection_visits_returned', 'required', 'text');
        $this->addColumn('government_inspection_visits_returned', 'uploaded_file', 'string', [255]);
        $this->addColumn('government_inspection_visits_returned', 'created_at', 'dateTime');
        $this->addColumn('government_inspection_visits_returned', 'created_by', 'integer');
        $this->addColumn('government_inspection_visits_returned', 'reply', 'text');
        $this->addColumn('government_inspection_visits_returned', 'replied_at', 'dateTime');
        $this->addColumn('government_inspection_visits_returned', 'replied_by', 'integer');

        $this->addColumn('government_inspection_visits_receipt_reports', 'created_at', 'dateTime');
        $this->addColumn('government_inspection_visits_receipt_reports', 'seen_by_sms_at', 'dateTime');
        $this->addColumn('government_inspection_visits_receipt_reports', 'seen_by_email_at', 'dateTime');

        // The old application stores a Unix timestamp as a string here.
        $this->addColumn('government_inspection_visits_timeline', 'date', 'string', [30]);
        $this->addColumn('government_inspection_visits_timeline', 'notice', 'string', [11]);
        $this->addColumn('government_inspection_visits_timeline', 'created_by', 'integer');
        $this->addColumn('government_inspection_visits_timeline', 'created_by_type', 'integer');
        $this->addColumn('government_inspection_visits_timeline', 'branch_id', 'integer', [], 0);

        $this->addIndexIfMissing('government_inspection_visits', ['sms_tocken'], 'gvi_visits_sms_token_idx');
        $this->addIndexIfMissing('government_inspection_visits_numbers', ['token'], 'gvi_numbers_token_idx');

        foreach ([
            'government_inspection_visits_abuses_and_notes',
            'government_inspection_visits_returned',
            'government_inspection_visits_receipt_reports',
            'government_inspection_visits_timeline',
        ] as $table) {
            $this->addIndexIfMissing($table, ['government_inspection_visits_id'], $table.'_visit_idx');
        }

        $this->backfillVisitTokens();
        $this->backfillChildMetadata();
        $this->backfillInspectionStatuses();
    }

    /**
     * This migration intentionally has no destructive rollback.  Dropping
     * restored columns could remove data written by the repaired workflow.
     */
    public function down(): void
    {
        // Intentionally left blank; production rollback must preserve data.
    }

    private function addColumn(
        string $tableName,
        string $columnName,
        string $type,
        array $parameters = [],
        mixed $default = null,
    ): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $type, $parameters, $default): void {
            $definition = $table->{$type}($columnName, ...$parameters)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        });
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach (Schema::getIndexes($tableName) as $index) {
            if (($index['columns'] ?? []) === $columns) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->index($columns, $indexName);
        });
    }

    private function backfillVisitTokens(): void
    {
        if (! Schema::hasTable('government_inspection_visits')) {
            return;
        }

        DB::table('government_inspection_visits')
            ->where(function ($query): void {
                $query->whereNull('sms_tocken')->orWhere('sms_tocken', '');
            })
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($visit): void {
                DB::table('government_inspection_visits')
                    ->where('id', $visit->id)
                    ->update(['sms_tocken' => md5('inspection-'.$visit->id.'-'.Str::uuid())]);
            });

        if (! Schema::hasTable('government_inspection_visits_numbers')) {
            return;
        }

        DB::table('government_inspection_visits_numbers')
            ->where(function ($query): void {
                $query->whereNull('token')->orWhere('token', '');
            })
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($number): void {
                DB::table('government_inspection_visits_numbers')
                    ->where('id', $number->id)
                    ->update(['token' => md5('inspection-number-'.$number->id.'-'.Str::uuid())]);
            });
    }

    private function backfillChildMetadata(): void
    {
        if (! Schema::hasTable('government_inspection_visits')) {
            return;
        }

        $visits = DB::table('government_inspection_visits')
            ->get(['id', 'visit_number', 'created_at', 'created_by', 'branch_id']);

        foreach ($visits as $visit) {
            if (Schema::hasTable('government_inspection_visits_numbers') && $visit->visit_number) {
                $this->backfillNullable('government_inspection_visits_numbers', 'id', $visit->visit_number, [
                    'created_at' => $visit->created_at,
                    'created_by' => $visit->created_by,
                ]);
            }

            foreach ([
                'government_inspection_visits_abuses_and_notes',
                'government_inspection_visits_returned',
                'government_inspection_visits_receipt_reports',
                'government_inspection_visits_timeline',
            ] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $values = match ($table) {
                    'government_inspection_visits_abuses_and_notes' => [
                        'date' => $visit->created_at !== null
                            ? $visit->created_at
                            : null,
                    ],
                    'government_inspection_visits_returned' => [
                        'created_at' => $visit->created_at,
                        'created_by' => $visit->created_by,
                    ],
                    'government_inspection_visits_receipt_reports' => [
                        'created_at' => $visit->created_at,
                    ],
                    default => [
                        'created_by' => $visit->created_by,
                        'branch_id' => $visit->branch_id,
                    ],
                };
                if ($table === 'government_inspection_visits_timeline') {
                    $values['date'] = $visit->created_at !== null
                        ? (string) strtotime((string) $visit->created_at)
                        : null;
                }
                $values = array_filter($values, static fn ($value): bool => $value !== null);
                if ($values === []) {
                    continue;
                }

                foreach ($values as $column => $value) {
                    DB::table($table)
                        ->where('government_inspection_visits_id', $visit->id)
                        ->whereNull($column)
                        ->update([$column => $value]);
                }
            }
        }
    }

    private function backfillNullable(string $table, string $key, int $id, array $values): void
    {
        foreach (array_filter($values, static fn ($value): bool => $value !== null) as $column => $value) {
            DB::table($table)
                ->where($key, $id)
                ->whereNull($column)
                ->update([$column => $value]);
        }
    }

    private function backfillInspectionStatuses(): void
    {
        if (! Schema::hasTable('government_inspection_status')) {
            return;
        }

        $statuses = [
            2 => ['تم الرد من قبل القسم', 'تم الرد من قبل القسم', 2],
            3 => ['تم تجاوز الفترة الزمنية المحددة', 'تم تجاوز الفترة الزمنية المحددة', 3],
            4 => ['تم تصعيد الطلب للإدارة', 'تم تصعيد الطلب للإدارة', 4],
            5 => ['تم الرد من قبل الإدارة', 'تم الرد من قبل الإدارة', 5],
            6 => ['تم إفادة الجهة الحكومية', 'تم إفادة الجهة الحكومية', 6],
            7 => ['تم إرجاع الطلب للقسم المعني', 'تم إرجاع الطلب للقسم المعني', 7],
        ];

        foreach ($statuses as $id => [$nameEn, $nameAr, $ranking]) {
            if (DB::table('government_inspection_status')->where('id', $id)->exists()) {
                continue;
            }

            DB::table('government_inspection_status')->insert([
                'id' => $id,
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'info' => null,
                'publish' => 1,
                'ranking' => $ranking,
            ]);
        }
    }
};
