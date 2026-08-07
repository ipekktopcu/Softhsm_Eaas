<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leaf_keys')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];

                foreach (['private_key', 'certificate'] as $column) {
                    $value = $row->{$column};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($value);
                    } catch (Throwable) {
                        $updates[$column] = Crypt::encryptString($value);
                    }
                }

                if ($updates !== []) {
                    DB::table('leaf_keys')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('leaf_keys')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];

                foreach (['private_key', 'certificate'] as $column) {
                    $value = $row->{$column};

                    if ($value === null || $value === '') {
                        continue;
                    }

                    try {
                        $updates[$column] = Crypt::decryptString($value);
                    } catch (Throwable) {
                        //
                    }
                }

                if ($updates !== []) {
                    DB::table('leaf_keys')->where('id', $row->id)->update($updates);
                }
            }
        });
    }
};
