<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inv_product_reclass_mappings', 'is_default')) {
            Schema::table('inv_product_reclass_mappings', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_active');
            });
        }

        DB::table('inv_product_reclass_mappings')->where('is_default', false)->update(['is_default' => true]);

        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('inv_product_reclass_mappings', function (Blueprint $table) {
                try {
                    $table->dropUnique(['source_product_id']);
                } catch (\Exception $e) {
                }
                
                try {
                    $table->unique(['source_product_id', 'target_product_id'], 'inv_prm_source_target_unique');
                } catch (\Exception $e) {
                }
                
                try {
                    $table->index(['source_product_id'], 'inv_prm_source_idx');
                } catch (\Exception $e) {
                }
                
                try {
                    $table->index(['source_product_id', 'is_default'], 'inv_prm_source_default_idx');
                } catch (\Exception $e) {
                }
            });
            return;
        }

        $dbName = DB::getDatabaseName();
        $tableName = 'inv_product_reclass_mappings';

        $hasIndex = fn (string $indexName) => DB::selectOne(
            "SELECT 1 AS v FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$dbName, $tableName, $indexName]
        ) !== null;

        if (!$hasIndex('inv_prm_source_idx')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `inv_prm_source_idx` (`source_product_id`)");
        }

        if ($hasIndex('inv_product_reclass_mappings_source_product_id_unique')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `inv_product_reclass_mappings_source_product_id_unique`");
        }

        if (!$hasIndex('inv_prm_source_target_unique')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD UNIQUE `inv_prm_source_target_unique` (`source_product_id`, `target_product_id`)");
        }

        if (!$hasIndex('inv_prm_source_default_idx')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD INDEX `inv_prm_source_default_idx` (`source_product_id`, `is_default`)");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('inv_product_reclass_mappings', function (Blueprint $table) {
                try {
                    $table->dropUnique('inv_prm_source_target_unique');
                } catch (\Exception $e) {}
                
                try {
                    $table->unique(['source_product_id'], 'inv_product_reclass_mappings_source_product_id_unique');
                } catch (\Exception $e) {}
            });
            return;
        }

        $dbName = DB::getDatabaseName();
        $tableName = 'inv_product_reclass_mappings';

        $hasIndex = fn (string $indexName) => DB::selectOne(
            "SELECT 1 AS v FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$dbName, $tableName, $indexName]
        ) !== null;

        if ($hasIndex('inv_prm_source_default_idx')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `inv_prm_source_default_idx`");
        }

        if ($hasIndex('inv_prm_source_target_unique')) {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `inv_prm_source_target_unique`");
        }

        if (!$hasIndex('inv_product_reclass_mappings_source_product_id_unique')) {
            DB::statement("ALTER TABLE `{$tableName}` ADD UNIQUE `inv_product_reclass_mappings_source_product_id_unique` (`source_product_id`)");
        }

        if (Schema::hasColumn('inv_product_reclass_mappings', 'is_default')) {
            Schema::table('inv_product_reclass_mappings', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
