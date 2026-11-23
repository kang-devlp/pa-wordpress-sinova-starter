<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sinova_website_documents')) {
            Schema::create('sinova_website_documents', function (Blueprint $table) {
                $table->id();
                $table->string('page_slug', 64);
                $table->unsignedSmallInteger('year');
                $table->string('sub_group', 64)->default('');
                $table->string('period_key', 32);
                $table->unsignedSmallInteger('period_order')->default(0);
                $table->string('title', 255)->default('');
                $table->string('source_type', 20)->default('external');
                $table->text('doc_url');
                $table->unsignedInteger('file_size_kb')->nullable();
                $table->string('sync_status', 20)->default('synced');
                $table->timestamp('last_synced_at')->nullable();
                $table->text('last_sync_error')->nullable();
                $table->unsignedSmallInteger('link_http_status')->nullable();
                $table->string('link_health_note', 160)->nullable();
                $table->timestamp('link_checked_at')->nullable();
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->string('updated_by_name', 120)->nullable();
                $table->timestamps();

                $table->unique(['page_slug', 'year', 'sub_group', 'period_key'], 'uq_sinova_doc_slot');
                $table->index(['page_slug', 'year'], 'idx_sinova_slug_year');
            });
        }

        if (! Schema::hasTable('sinova_website_audit_logs')) {
            Schema::create('sinova_website_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 120);
                $table->string('action', 32);
                $table->string('page_slug', 64);
                $table->unsignedSmallInteger('year')->nullable();
                $table->string('sub_group', 64)->nullable();
                $table->string('period_key', 32)->nullable();
                $table->string('source_type', 20)->nullable();
                $table->text('doc_url')->nullable();
                $table->boolean('sync_ok')->default(true);
                $table->string('message', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['page_slug', 'created_at'], 'idx_sinova_audit_slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sinova_website_audit_logs');
        Schema::dropIfExists('sinova_website_documents');
    }
};
