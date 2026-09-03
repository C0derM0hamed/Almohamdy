<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gov_account_notices')) {
            Schema::create('gov_account_notices', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('companies_groups_id')->index();
                $table->string('title');
                $table->unsignedInteger('authority_id')->index();
                $table->unsignedInteger('service_id')->nullable()->index();
                $table->text('description');
                $table->date('event_date')->index();
                $table->time('event_time');
                $table->string('meeting_url', 2048)->nullable();
                $table->string('attendance_method', 20);
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->json('targeting');
                $table->unsignedInteger('created_by')->index();
                $table->dateTime('sent_at')->nullable()->index();
                $table->boolean('publish')->default(true)->index();
                $table->timestamps();
                $table->index(['companies_groups_id', 'publish', 'event_date'], 'gov_notice_scope_date_idx');
            });
        }

        if (! Schema::hasTable('gov_account_notice_recipients')) {
            Schema::create('gov_account_notice_recipients', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('notice_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->string('email');
                $table->string('token', 64)->unique();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('viewed_at')->nullable()->index();
                $table->unsignedInteger('view_count')->default(0);
                $table->dateTime('last_viewed_at')->nullable();
                $table->timestamps();
                $table->unique(['notice_id', 'user_id'], 'gov_notice_recipient_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gov_account_notice_recipients');
        Schema::dropIfExists('gov_account_notices');
    }
};
