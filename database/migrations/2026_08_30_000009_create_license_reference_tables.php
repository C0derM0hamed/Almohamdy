<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_authorities')) {
            Schema::create('license_authorities', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->string('name_ar');
                $table->string('name_en');
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
                $table->timestamps();

                $table->index(['companies_groups_id', 'publish', 'ranking'], 'lic_auth_scope_idx');
            });
        }

        if (! Schema::hasTable('license_types')) {
            Schema::create('license_types', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->string('name_ar');
                $table->string('name_en');
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
                $table->timestamps();

                $table->index(['companies_groups_id', 'publish', 'ranking'], 'lic_type_scope_idx');
            });
        }

        if (! Schema::hasTable('license_statuses')) {
            Schema::create('license_statuses', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('code', 40)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('info', 20)->nullable();
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
            });
        }

        if (! Schema::hasTable('license_renewal_stages')) {
            Schema::create('license_renewal_stages', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('code', 50)->unique();
                $table->string('name_ar');
                $table->string('name_en');
                $table->boolean('publish')->default(true)->index();
                $table->unsignedInteger('ranking')->default(0);
                $table->timestamps();
            });
        }

        $this->seedStatuses();
        $this->seedRenewalStages();
    }

    public function down(): void
    {
        Schema::dropIfExists('license_renewal_stages');
        Schema::dropIfExists('license_statuses');
        Schema::dropIfExists('license_types');
        Schema::dropIfExists('license_authorities');
    }

    private function seedStatuses(): void
    {
        if (! Schema::hasTable('license_statuses')) {
            return;
        }

        $statuses = [
            ['code' => 'active', 'name_ar' => 'ساري', 'name_en' => 'Active', 'info' => '#15803d', 'ranking' => 10],
            ['code' => 'near_expiry', 'name_ar' => 'قريب الانتهاء', 'name_en' => 'Near expiry', 'info' => '#ca8a04', 'ranking' => 20],
            ['code' => 'under_renewal', 'name_ar' => 'تحت التجديد', 'name_en' => 'Under renewal', 'info' => '#2563eb', 'ranking' => 30],
            ['code' => 'expired', 'name_ar' => 'منتهي', 'name_en' => 'Expired', 'info' => '#dc2626', 'ranking' => 40],
            ['code' => 'renewed', 'name_ar' => 'تم التجديد', 'name_en' => 'Renewed', 'info' => '#0f766e', 'ranking' => 50],
        ];

        foreach ($statuses as $status) {
            DB::table('license_statuses')->updateOrInsert(
                ['code' => $status['code']],
                $status + ['publish' => true],
            );
        }
    }

    private function seedRenewalStages(): void
    {
        if (! Schema::hasTable('license_renewal_stages')) {
            return;
        }

        $stages = [
            ['code' => 'not_started', 'name_ar' => 'لم يبدأ', 'name_en' => 'Not started', 'ranking' => 10],
            ['code' => 'preparing', 'name_ar' => 'جارٍ التجهيز', 'name_en' => 'Preparing', 'ranking' => 20],
            ['code' => 'awaiting_payment', 'name_ar' => 'بانتظار السداد', 'name_en' => 'Awaiting payment', 'ranking' => 30],
            ['code' => 'submitted', 'name_ar' => 'مقدم للجهة', 'name_en' => 'Submitted to authority', 'ranking' => 40],
            ['code' => 'completed', 'name_ar' => 'مكتمل', 'name_en' => 'Completed', 'ranking' => 50],
        ];

        foreach ($stages as $stage) {
            DB::table('license_renewal_stages')->updateOrInsert(
                ['code' => $stage['code']],
                $stage + ['publish' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
};
