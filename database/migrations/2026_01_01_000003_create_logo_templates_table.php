<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logo_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('template_token')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('style_id')->nullable()->constrained('logo_styles')->nullOnDelete();
            $table->foreignId('default_font_id')->nullable()->constrained('fonts')->nullOnDelete();
            $table->foreignId('symbol_asset_id')->nullable()->constrained('logo_assets')->nullOnDelete();
            $table->string('layout', 40)->default('icon-left');
            $table->string('orientation', 40)->default('horizontal');
            $table->json('keywords')->nullable();
            $table->json('design_json')->nullable();
            $table->longText('svg')->nullable();
            $table->string('preview_path')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('popularity')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['industry_id', 'is_active']);
            $table->index(['style_id', 'is_active']);
            $table->index(['is_active', 'is_premium']);
            $table->index(['layout', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logo_templates');
    }
};
