<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logo_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('business_name', 120);
            $table->string('normalized_name', 120)->index();
            $table->string('engine', 32)->default('template');
            $table->string('status', 24)->default('ready');
            $table->json('intent')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('guest_token')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('generated_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generation_id')->constrained('logo_generations')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('logo_templates')->nullOnDelete();
            $table->foreignId('symbol_asset_id')->nullable()->constrained('logo_assets')->nullOnDelete();
            $table->foreignId('font_id')->nullable()->constrained('fonts')->nullOnDelete();
            $table->string('style_slug', 40)->nullable();
            $table->string('layout', 40)->default('icon-left');
            $table->string('orientation', 40)->default('horizontal');
            $table->json('palette')->nullable();
            $table->json('design_json');
            $table->longText('svg');
            $table->decimal('score', 8, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_ai_symbol')->default(false);
            $table->timestamps();

            $table->index(['generation_id', 'position']);
            $table->index(['generation_id', 'is_premium']);
            $table->index('score');
            $table->index('created_at');
        });

        Schema::create('user_designs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('guest_token')->nullable()->index();
            $table->foreignId('generation_id')->nullable()->constrained('logo_generations')->nullOnDelete();
            $table->foreignId('generated_logo_id')->nullable()->constrained('generated_logos')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('logo_templates')->nullOnDelete();
            $table->string('name');
            $table->string('business_name', 120);
            $table->string('tagline', 160)->nullable();
            $table->json('design_json');
            $table->longText('svg')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'is_draft']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_design_id')->nullable()->constrained('user_designs')->cascadeOnDelete();
            $table->foreignId('generated_logo_id')->nullable()->constrained('generated_logos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'user_design_id']);
            $table->unique(['user_id', 'generated_logo_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('user_designs');
        Schema::dropIfExists('generated_logos');
        Schema::dropIfExists('logo_generations');
    }
};
