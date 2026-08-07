<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csr_files', function (Blueprint $table) {
            $table->boolean('is_signed')->default(false)->after('file_path');
            $table->text('private_key_pem')->nullable()->after('is_signed');
            $table->text('certificate_pem')->nullable()->after('private_key_pem');
            $table->string('serial_number')->nullable()->unique()->after('certificate_pem');
            $table->timestamp('issued_at')->nullable()->after('serial_number');
            $table->timestamp('expires_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('csr_files', function (Blueprint $table) {
            $table->dropColumn([
                'is_signed',
                'private_key_pem',
                'certificate_pem',
                'serial_number',
                'issued_at',
                'expires_at',
            ]);
        });
    }
};