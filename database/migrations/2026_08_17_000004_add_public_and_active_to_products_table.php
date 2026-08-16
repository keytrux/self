<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('user_id');
            $table->boolean('is_active')->default(true)->after('is_public');
        });

        // Legacy: общие продукты (user_id IS NULL) становятся публичными.
        DB::table('products')->whereNull('user_id')->update(['is_public' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'is_active']);
        });
    }
};
