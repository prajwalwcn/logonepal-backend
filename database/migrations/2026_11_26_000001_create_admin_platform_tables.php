<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description', 512)->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('is_admin')->constrained('admin_roles')->nullOnDelete();
            $table->string('status', 24)->default('active')->after('role_id')->index();
            $table->timestamp('last_active_at')->nullable()->after('status')->index();
        });

        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80)->index();
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 512);
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 60)->index();
            $table->string('workflow', 60)->index();
            $table->longText('content');
            $table->json('variables')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 24)->default('draft')->index();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_model_prices', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('model', 120);
            $table->decimal('input_per_million', 12, 6)->default(0);
            $table->decimal('output_per_million', 12, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider', 'model']);
            $table->index(['provider', 'is_active']);
        });

        Schema::table('ai_generation_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('job_id', 80)->nullable()->after('status');
            $table->decimal('estimated_cost', 14, 6)->default(0)->after('latency_ms');
            $table->string('currency', 3)->default('USD')->after('estimated_cost');
            $table->string('review_status', 24)->default('not_required')->after('currency')->index();
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('template_categories', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('icon');
            $table->string('meta_title')->nullable()->after('description');
            $table->string('meta_description', 512)->nullable()->after('meta_title');
        });

        Schema::table('user_designs', function (Blueprint $table) {
            $table->string('moderation_status', 24)->default('approved')->after('is_archived')->index();
            $table->foreignId('moderated_by')->nullable()->after('moderation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->text('moderation_note')->nullable()->after('moderated_at');
        });

        Schema::table('logo_assets', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->string('category', 40)->default('symbol')->after('asset_type')->index();
            $table->string('license', 40)->default('platform')->after('is_active');
            $table->string('attribution')->nullable()->after('license');
            $table->boolean('commercial_use')->default(true)->after('attribution');
            $table->string('source')->nullable()->after('commercial_use');
            $table->string('mime_type', 80)->nullable()->after('source');
            $table->string('format', 16)->nullable()->after('mime_type');
            $table->unsignedBigInteger('file_size')->nullable()->after('format');
            $table->unsignedInteger('width')->nullable()->after('file_size');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->boolean('is_featured')->default(false)->after('height')->index();
            $table->boolean('is_archived')->default(false)->after('is_featured')->index();
        });

        Schema::table('logo_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('status', 24)->default('published')->after('is_active')->index();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('email');
            $table->string('subject');
            $table->text('description');
            $table->string('category', 40)->default('support')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 24)->default('open')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('type', 40)->default('page')->index();
            $table->string('excerpt', 512)->nullable();
            $table->longText('body')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('sections')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 512)->nullable();
            $table->string('og_image')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category', 40)->default('general')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type', 24)->default('info');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reportable_type', 120);
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason', 80);
            $table->text('details')->nullable();
            $table->string('status', 24)->default('open')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id'], 'content_reports_reportable_index');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('content_pages');
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('ai_model_prices');
        Schema::dropIfExists('ai_prompts');

        Schema::table('logo_templates', function (Blueprint $table) {
            $table->dropColumn(['description', 'status']);
        });

        Schema::table('logo_assets', function (Blueprint $table) {
            $table->dropColumn([
                'description', 'category', 'license', 'attribution', 'commercial_use',
                'source', 'mime_type', 'format', 'file_size', 'width', 'height',
                'is_featured', 'is_archived',
            ]);
        });

        Schema::table('user_designs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['moderation_status', 'moderated_at', 'moderation_note']);
        });

        Schema::table('template_categories', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'meta_title', 'meta_description']);
        });

        Schema::table('ai_generation_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'job_id', 'estimated_cost', 'currency', 'review_status',
                'reviewed_at', 'review_notes',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['status', 'last_active_at']);
        });

        Schema::dropIfExists('admin_activity_logs');
        Schema::dropIfExists('admin_roles');
    }
};
