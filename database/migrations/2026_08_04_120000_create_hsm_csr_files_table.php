<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedicated table for HSM dashboard CSRs. Kept separate from csr_files,
     * which is reserved for the leaf_keys -> csr -> sign -> pfx workflow.
     * No unique constraint on (user_id, key_label): the same HSM key may
     * generate multiple CSRs without overwriting each other.
     */
    public function up(): void
    {
        Schema::create('hsm_csr_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key_label');
            $table->string('common_name');
            $table->string('organization')->nullable();
            $table->string('country', 2);
            $table->string('file_path');
            $table->boolean('is_signed')->default(false);
            $table->text('certificate_pem')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'key_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsm_csr_files');
    }
};
