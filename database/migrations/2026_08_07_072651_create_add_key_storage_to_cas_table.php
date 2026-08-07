<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cas', function (Blueprint $table) {
            // private_key alanının içeriğinin ne olduğunu belirtir:
            // 'file'   -> private_key sütunu gerçek PEM private key içerir (eski/legacy kayıtlar)
            // 'pkcs11' -> private_key sütunu bir PKCS#11 URI'sidir (pkcs11:token=...;object=...),
            //             gerçek key materyali HSM içinde kalır ve DB'ye hiç yazılmaz.
            $table->enum('key_storage', ['file', 'pkcs11'])
                ->default('file')
                ->after('private_key');
        });
    }
 
    public function down(): void
    {
        Schema::table('cas', function (Blueprint $table) {
            $table->dropColumn('key_storage');
        });
    }
};
 