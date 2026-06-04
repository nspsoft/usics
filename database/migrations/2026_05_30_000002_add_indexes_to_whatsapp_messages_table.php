<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return;
        }

        if (!Schema::hasColumn('whatsapp_messages', 'direction') || !Schema::hasColumn('whatsapp_messages', 'is_read') || !Schema::hasColumn('whatsapp_messages', 'phone')) {
            return;
        }

        $indexName = 'whatsapp_messages_direction_is_read_phone_index';
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            if ($driver === 'sqlite') {
                $exists = DB::table('sqlite_master')
                    ->where('type', 'index')
                    ->where('name', $indexName)
                    ->exists();
                if ($exists) {
                    return;
                }
            } else {
                return;
            }
        } else {
            $dbName = DB::selectOne('select database() as name')?->name;
            if (!$dbName) {
                return;
            }

            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', $dbName)
                ->where('table_name', 'whatsapp_messages')
                ->where('index_name', $indexName)
                ->exists();

            if ($exists) {
                return;
            }
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) use ($indexName) {
            $table->index(['direction', 'is_read', 'phone'], $indexName);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            return;
        }

        $indexName = 'whatsapp_messages_direction_is_read_phone_index';
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $dbName = DB::selectOne('select database() as name')?->name;
            if ($dbName) {
                $exists = DB::table('information_schema.statistics')
                    ->where('table_schema', $dbName)
                    ->where('table_name', 'whatsapp_messages')
                    ->where('index_name', $indexName)
                    ->exists();

                if (!$exists) {
                    return;
                }
            }
        } elseif ($driver === 'sqlite') {
            $exists = DB::table('sqlite_master')
                ->where('type', 'index')
                ->where('name', $indexName)
                ->exists();
            if (!$exists) {
                return;
            }
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};

