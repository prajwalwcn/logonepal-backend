<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logo_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('view_box', 64)->default('0 0 64 64');
            /** @var array<int, array{d: string, tone?: int}> $paths */
            $table->json('paths');
            $table->json('keywords')->nullable();
            $table->json('style_slugs')->nullable();
            $table->json('industry_slugs')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_premium']);
        });

        Schema::create('fonts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('family');
            $table->string('google_family')->nullable();
            $table->string('category', 32)->default('sans');
            $table->json('weights')->nullable();
            $table->boolean('has_italic')->default(false);
            $table->string('preview_text')->nullable();
            $table->json('letter_spacing_by_weight')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('color_palettes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('colors');
            $table->string('mood', 64)->nullable();
            $table->boolean('is_dark')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['mood', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('color_palettes');
        Schema::dropIfExists('fonts');
        Schema::dropIfExists('logo_assets');
    }
};
