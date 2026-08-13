<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'departmentId')) {
            if (DB::getDriverName() === 'mysql') {
                $database = DB::getDatabaseName();
                $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->select('CONSTRAINT_NAME')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', 'users')
                    ->where('COLUMN_NAME', 'departmentId')
                    ->whereNotNull('REFERENCED_TABLE_NAME')
                    ->pluck('CONSTRAINT_NAME');

                foreach ($constraints as $constraint) {
                    DB::statement("ALTER TABLE users DROP FOREIGN KEY {$constraint}");
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('departmentId');
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::drop('departments');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'departmentId')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('departmentId')->nullable()->after('roleId');
            });
        }

        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('departmentName', 100)->unique();
                $table->timestamp('createdAt')->nullable();
            });
        }
    }
};
