<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cas', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->enum('level', ['root', 'intermediate'])->default('intermediate');
            $table->string('common_name');
            $table->string('organization')->nullable();
            $table->string('country', 2)->nullable();
            $table->text('private_key');
            $table->text('certificate');
            $table->string('serial_number')->nullable();
            $table->string('issuer_label')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->string('fingerprint_sha256', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cas');
    }
};