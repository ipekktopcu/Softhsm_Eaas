<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make key labels unique per user instead of globally, so each user
     * can manage their own isolated keys without collisions.
     */
    public function up(): void
    {
        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->dropUnique('hsm_keys_label_unique');
            $table->unique(['user_id', 'label'], 'hsm_keys_user_id_label_unique');
        });

        Schema::table('leaf_keys', function (Blueprint $table) {
            $table->dropUnique('leaf_keys_label_unique');
            $table->unique(['user_id', 'label'], 'leaf_keys_user_id_label_unique');
        });

        Schema::table('csr_files', function (Blueprint $table) {
            $table->unique(['user_id', 'key_label'], 'csr_files_user_id_key_label_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csr_files', function (Blueprint $table) {
            $table->dropUnique('csr_files_user_id_key_label_unique');
            $table->unique('key_label');
        });

        Schema::table('leaf_keys', function (Blueprint $table) {
            $table->dropUnique('leaf_keys_user_id_label_unique');
            $table->unique('label');
        });

        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->dropUnique('hsm_keys_user_id_label_unique');
            $table->unique('label');
        });
    }
};
