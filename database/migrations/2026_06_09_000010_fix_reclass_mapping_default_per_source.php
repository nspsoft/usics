<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'inv_product_reclass_mappings';

        DB::statement("UPDATE `{$table}` SET `is_default` = 0 WHERE `deleted_at` IS NULL");

        DB::statement("
            UPDATE `{$table}` m
            INNER JOIN (
                SELECT source_product_id, MIN(id) AS min_id
                FROM `{$table}`
                WHERE deleted_at IS NULL AND is_active = 1
                GROUP BY source_product_id
            ) x ON x.min_id = m.id
            SET m.is_default = 1
        ");
    }

    public function down(): void
    {
    }
};

