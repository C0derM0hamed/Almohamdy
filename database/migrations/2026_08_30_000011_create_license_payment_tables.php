<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_payment_request_statuses')) {
            Schema::create('license_payment_request_statuses', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('code', 40)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('info', 20)->nullable();
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
            });
        }

        if (! Schema::hasTable('license_payment_requests')) {
            Schema::create('license_payment_requests', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->index();
                $table->unsignedInteger('renewal_id')->nullable()->index();
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('SAR');
                $table->string('bank_name')->nullable();
                $table->string('account_iban', 64)->nullable();
                $table->text('transfer_details')->nullable();
                $table->string('invoice_number')->nullable()->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('status_id')->index();
                $table->unsignedInteger('requested_by')->index();
                $table->dateTime('closed_at')->nullable()->index();
                $table->timestamps();

                $table->index(['license_id', 'status_id'], 'lic_payment_license_status_idx');
            });
        }

        if (! Schema::hasTable('license_payment_events')) {
            Schema::create('license_payment_events', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('payment_request_id')->index();
                $table->unsignedInteger('status_id')->nullable()->index();
                $table->text('comment')->nullable();
                $table->string('event_type', 50)->index();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->dateTime('created_at')->index();

                $table->index(['payment_request_id', 'created_at'], 'lic_payment_event_time_idx');
            });
        }

        $this->seedStatuses();
    }

    public function down(): void
    {
        Schema::dropIfExists('license_payment_events');
        Schema::dropIfExists('license_payment_requests');
        Schema::dropIfExists('license_payment_request_statuses');
    }

    private function seedStatuses(): void
    {
        if (! Schema::hasTable('license_payment_request_statuses')) {
            return;
        }

        $statuses = [
            ['code' => 'received', 'name_ar' => 'تم استلام الطلب', 'name_en' => 'Received', 'info' => '#2563eb', 'ranking' => 10],
            ['code' => 'in_progress', 'name_ar' => 'تم البدء بإجراءات السداد', 'name_en' => 'Payment in progress', 'info' => '#ca8a04', 'ranking' => 20],
            ['code' => 'needs_documents', 'name_ar' => 'بحاجة إلى مرفقات', 'name_en' => 'Needs documents', 'info' => '#dc2626', 'ranking' => 30],
            ['code' => 'paid', 'name_ar' => 'تم السداد', 'name_en' => 'Paid', 'info' => '#15803d', 'ranking' => 40],
        ];

        foreach ($statuses as $status) {
            DB::table('license_payment_request_statuses')->updateOrInsert(
                ['code' => $status['code']],
                $status + ['publish' => true],
            );
        }
    }
};
