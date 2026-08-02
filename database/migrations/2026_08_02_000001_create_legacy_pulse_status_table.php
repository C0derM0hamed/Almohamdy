<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Pulseـstatus')) {
            Schema::create('Pulseـstatus', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 200);
                $table->string('no', 12);
                $table->unsignedInteger('doctor');
                $table->dateTime('date_dlivry');
                $table->dateTime('Notification_date');
                $table->unsignedInteger('branch_id');
                $table->unsignedInteger('group_id');
                $table->unsignedTinyInteger('status')->default(0);
                $table->dateTime('create_at');
                $table->unsignedInteger('user_id');
                $table->string('Report_number', 100);
                $table->index(['branch_id', 'group_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('Pulseـstatus');
    }
};
