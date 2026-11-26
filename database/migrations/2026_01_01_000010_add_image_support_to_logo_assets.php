<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logo_assets', function (Blueprint $table) {
            $table->string('asset_type', 16)->default('svg')->after('view_box');
            $table->text('image_url')->nullable()->after('asset_type');
        });
    }

    public function down(): void
    {
        Schema::table('logo_assets', function (Blueprint $table) {
            $table->dropColumn(['asset_type', 'image_url']);
        });
    }
};
