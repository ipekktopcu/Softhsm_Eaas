<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hsm_csr_files', function (Blueprint $table) {
            $table->string('issuer_label')->nullable()->after('is_signed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hsm_csr_files', function (Blueprint $table) {
            //
        });
    }
};
