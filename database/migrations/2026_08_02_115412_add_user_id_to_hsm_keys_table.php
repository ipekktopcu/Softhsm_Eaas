<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        DB::table('hsm_keys')->whereNull('user_id')->update(['user_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
