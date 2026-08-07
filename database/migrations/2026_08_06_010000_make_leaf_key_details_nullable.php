<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaf_keys', function (Blueprint $table) {
            $table->string('common_name')->nullable()->change();
            $table->string('organization')->nullable()->change();
            $table->string('country', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leaf_keys', function (Blueprint $table) {
            $table->string('common_name')->nullable(false)->change();
            $table->string('organization')->nullable(false)->change();
            $table->string('country', 2)->nullable(false)->change();
        });
    }
};
