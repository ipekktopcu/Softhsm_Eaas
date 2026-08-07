<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csr_files', function (Blueprint $table) {
            $table->id();
            $table->string('key_label');
            $table->string('common_name');
            $table->string('organization');
            $table->string('country', 2);
            $table->string('file_path');
            $table->timestamps(); // created_at + updated_at otomatik gelir
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csr_files');
    }
};