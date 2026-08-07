<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csr_files', function (Blueprint $table) {
            $table->dropUnique('csr_files_user_id_key_label_unique');
        });
    }

    public function down(): void
    {
        Schema::table('csr_files', function (Blueprint $table) {
            $table->unique(['user_id', 'key_label']);
        });
    }
};