<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pfx_certs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('key_id')->constrained('leaf_keys')->onDelete('cascade');
            $table->string('label');
            $table->string('serial_number')->unique();
            $table->text('issuer');
            $table->text('subject');
            $table->string('signature_algorithm');
            $table->string('public_key_algorithm');
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->text('certificate_pem');
            $table->text('chain_pem')->nullable();
            $table->string('fingerprint_sha256', 64)->unique();
            $table->string('issuer_label')->nullable();
            $table->enum('status', ['valid', 'revoked', 'expired'])->default('valid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pfx_certs');
    }
};