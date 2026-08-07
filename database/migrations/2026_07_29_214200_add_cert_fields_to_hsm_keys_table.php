<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->string('common_name')->nullable()->after('label');
            $table->string('csr_path')->nullable()->after('common_name');
            $table->string('cert_path')->nullable()->after('csr_path');
            $table->timestamp('cert_issued_at')->nullable()->after('cert_path');
        });
    }

    public function down(): void
    {
        Schema::table('hsm_keys', function (Blueprint $table) {
            $table->dropColumn(['common_name', 'csr_path', 'cert_path', 'cert_issued_at']);
        });
    }
};