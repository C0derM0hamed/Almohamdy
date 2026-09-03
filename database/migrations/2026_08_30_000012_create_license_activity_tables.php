<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_comments')) {
            Schema::create('license_comments', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->text('body');
                $table->boolean('publish')->default(true)->index();
                $table->dateTime('created_at')->index();

                $table->index(['license_id', 'publish', 'created_at'], 'lic_comment_visible_idx');
            });
        }

        if (! Schema::hasTable('license_attachments')) {
            Schema::create('license_attachments', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->index();
                $table->unsignedInteger('renewal_id')->nullable()->index();
                $table->unsignedInteger('payment_request_id')->nullable()->index();
                $table->string('context', 40)->default('license')->index();
                $table->string('file_path', 1024);
                $table->string('original_name');
                $table->string('mime', 150);
                $table->unsignedBigInteger('size');
                $table->text('description')->nullable();
                $table->unsignedInteger('uploaded_by')->index();
                $table->dateTime('uploaded_at')->index();

                $table->index(['license_id', 'context'], 'lic_attachment_context_idx');
            });
        }

        if (! Schema::hasTable('license_timeline')) {
            Schema::create('license_timeline', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('license_id')->nullable()->index();
                $table->string('event_type', 60)->index();
                $table->unsignedInteger('status_id')->nullable()->index();
                $table->text('notice')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->string('created_by_type', 40)->default('user');
                $table->unsignedInteger('branch_id')->nullable()->index();
                $table->dateTime('date')->index();

                $table->index(['license_id', 'date'], 'lic_timeline_license_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_timeline');
        Schema::dropIfExists('license_attachments');
        Schema::dropIfExists('license_comments');
    }
};
