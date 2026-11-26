<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_designs', function (Blueprint $table) {
            $table->string('format', 80)->nullable()->after('template_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_designs', function (Blueprint $table) {
            $table->dropIndex(['format']);
            $table->dropColumn('format');
        });
    }
};
