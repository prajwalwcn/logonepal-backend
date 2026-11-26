<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->foreignId('platform_id')->nullable()->constrained('social_platforms')->nullOnDelete();
            $table->foreignId('design_type_id')->nullable()->constrained('social_design_types')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('template_categories')->nullOnDelete();

            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('aspect_ratio', 16)->default('1:1');

            $table->json('styles')->nullable();
            $table->json('keywords')->nullable();
            $table->json('tags')->nullable();
            $table->json('palette')->nullable();
            $table->json('fonts')->nullable();

            $table->string('preview_path')->nullable();
            $table->string('thumbnail_path')->nullable();

            $table->json('design_json');
            $table->longText('svg')->nullable();
            $table->json('layer_config')->nullable();
            $table->json('asset_refs')->nullable();

            $table->string('license_type', 32)->default('standard');
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);

            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('source', 20)->default('manual');
            $table->string('fingerprint', 64)->nullable();
            $table->json('ai_meta')->nullable();

            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->json('quality_report')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);

            $table->timestamps();

            $table->index(['status', 'is_premium']);
            $table->index(['platform_id', 'design_type_id']);
            $table->index(['industry_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['is_trending', 'status']);
            $table->index('created_at');
            $table->index('usage_count');
            $table->index('fingerprint');
        });

        Schema::create('social_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('social_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('name')->nullable();
            $table->json('design_json');
            $table->longText('svg')->nullable();
            $table->string('preview_path')->nullable();
            $table->text('changelog')->nullable();
            $table->json('quality_report')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['template_id', 'version']);
        });

        Schema::create('social_template_variations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('template_id')->constrained('social_templates')->cascadeOnDelete();
            $table->string('name');
            $table->json('design_json');
            $table->longText('svg')->nullable();
            $table->string('preview_path')->nullable();
            $table->string('fingerprint', 64);
            $table->string('status', 20)->default('draft');
            $table->json('meta')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['template_id', 'fingerprint']);
            $table->index(['template_id', 'status']);
        });

        Schema::create('social_template_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
        });

        Schema::create('social_template_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('social_template_collections')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('social_templates')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['collection_id', 'template_id'], 'social_collection_template_unique');
        });

        Schema::create('social_template_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('social_templates')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'template_id']);
            $table->index('template_id');
        });

        Schema::create('social_template_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('social_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 24);
            $table->string('ip_address', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['template_id', 'event', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_template_events');
        Schema::dropIfExists('social_template_favorites');
        Schema::dropIfExists('social_template_collection_items');
        Schema::dropIfExists('social_template_collections');
        Schema::dropIfExists('social_template_variations');
        Schema::dropIfExists('social_template_versions');
        Schema::dropIfExists('social_templates');
    }
};
