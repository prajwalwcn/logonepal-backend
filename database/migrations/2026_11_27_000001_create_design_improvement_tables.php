<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('color_palettes', function (Blueprint $table) {
            if (! Schema::hasColumn('color_palettes', 'category')) {
                $table->string('category', 64)->nullable()->after('mood');
            }
            if (! Schema::hasColumn('color_palettes', 'industry_slug')) {
                $table->string('industry_slug', 64)->nullable()->after('category');
            }
            if (! Schema::hasColumn('color_palettes', 'background_color')) {
                $table->string('background_color', 16)->nullable()->after('industry_slug');
            }
            if (! Schema::hasColumn('color_palettes', 'heading_color')) {
                $table->string('heading_color', 16)->nullable()->after('background_color');
            }
            if (! Schema::hasColumn('color_palettes', 'body_color')) {
                $table->string('body_color', 16)->nullable()->after('heading_color');
            }
            if (! Schema::hasColumn('color_palettes', 'accent_color')) {
                $table->string('accent_color', 16)->nullable()->after('body_color');
            }
            if (! Schema::hasColumn('color_palettes', 'contrast_ratio')) {
                $table->decimal('contrast_ratio', 5, 2)->nullable()->after('accent_color');
            }
        });

        Schema::create('typography_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 64)->default('modern');
            $table->string('description', 500)->nullable();
            $table->string('heading_font', 120);
            $table->string('body_font', 120);
            $table->string('accent_font', 120)->nullable();
            $table->unsignedSmallInteger('heading_weight')->default(700);
            $table->unsignedSmallInteger('body_weight')->default(400);
            $table->decimal('scale_ratio', 4, 2)->default(1.33);
            $table->decimal('line_height_heading', 4, 2)->default(1.12);
            $table->decimal('line_height_body', 4, 2)->default(1.45);
            $table->integer('letter_spacing_heading')->default(-10);
            $table->integer('letter_spacing_body')->default(0);
            $table->string('text_alignment', 16)->default('left');
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('layout_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('layout_type', 64);
            $table->string('description', 500)->nullable();
            $table->json('compatible_formats')->nullable();
            $table->json('proportions');
            $table->string('alignment', 16)->default('left');
            $table->json('safe_area')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['layout_type', 'is_active']);
        });

        Schema::create('design_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('style_key', 64);
            $table->string('description', 500)->nullable();
            $table->foreignId('color_palette_id')->nullable()->constrained('color_palettes')->nullOnDelete();
            $table->foreignId('typography_preset_id')->nullable()->constrained('typography_presets')->nullOnDelete();
            $table->foreignId('layout_preset_id')->nullable()->constrained('layout_presets')->nullOnDelete();
            $table->string('heading_font', 120)->default('Poppins');
            $table->string('body_font', 120)->default('Inter');
            $table->json('font_hierarchy')->nullable();
            $table->json('spacing_rules')->nullable();
            $table->string('layout_type', 64)->default('centered');
            $table->string('image_placement', 64)->default('right');
            $table->string('background_style', 64)->default('solid');
            $table->string('shape_style', 64)->default('clean');
            $table->json('decoration_rules')->nullable();
            $table->json('border_shadow_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['style_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_presets');
        Schema::dropIfExists('layout_presets');
        Schema::dropIfExists('typography_presets');

        Schema::table('color_palettes', function (Blueprint $table) {
            $cols = ['category', 'industry_slug', 'background_color', 'heading_color', 'body_color', 'accent_color', 'contrast_ratio'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('color_palettes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
