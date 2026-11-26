<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 64)->nullable();
            $table->string('accent_color', 16)->default('#2563EB');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('social_design_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('social_platforms')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('aspect_ratio', 16)->default('1:1');
            $table->json('safe_area')->nullable();
            $table->json('export_formats')->nullable();
            $table->string('layout_family', 32)->default('social');
            $table->string('variant', 32)->default('post');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['platform_id', 'slug']);
            $table->index(['is_active', 'sort_order']);
            $table->index(['width', 'height']);
        });

        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('template_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('template_styles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('template_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_tags');
        Schema::dropIfExists('template_styles');
        Schema::dropIfExists('template_categories');
        Schema::dropIfExists('social_design_types');
        Schema::dropIfExists('social_platforms');
    }
};
