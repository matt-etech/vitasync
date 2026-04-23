<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('client_assessments', 'version')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('client_id');
            });
        }

        if (! $this->usesMysql()) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropUnique(['client_id']);
                $table->unique(['client_id', 'version']);
                $table->index(['client_id', 'status']);
            });

            return;
        }

        if ($this->foreignKeyExists('client_assessments_client_id_foreign')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
        }

        if ($this->indexExists('client_assessments_client_id_unique')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropUnique('client_assessments_client_id_unique');
            });
        }

        if (! $this->indexExists('client_assessments_client_id_version_unique')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->unique(['client_id', 'version']);
            });
        }

        if (! $this->indexExists('client_assessments_client_id_status_index')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->index(['client_id', 'status']);
            });
        }

        if (! $this->foreignKeyExists('client_assessments_client_id_foreign')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! $this->usesMysql()) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropIndex(['client_id', 'status']);
                $table->dropUnique(['client_id', 'version']);
                $table->dropColumn('version');
                $table->unique('client_id');
            });

            return;
        }

        if ($this->foreignKeyExists('client_assessments_client_id_foreign')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
        }

        if ($this->indexExists('client_assessments_client_id_status_index')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropIndex('client_assessments_client_id_status_index');
            });
        }

        if ($this->indexExists('client_assessments_client_id_version_unique')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropUnique('client_assessments_client_id_version_unique');
            });
        }

        if (Schema::hasColumn('client_assessments', 'version')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }

        if (! $this->indexExists('client_assessments_client_id_unique')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->unique('client_id');
            });
        }

        if (! $this->foreignKeyExists('client_assessments_client_id_foreign')) {
            Schema::table('client_assessments', function (Blueprint $table) {
                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            });
        }
    }

    private function usesMysql(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function indexExists(string $indexName): bool
    {
        if (! $this->usesMysql()) {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'client_assessments')
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $constraintName): bool
    {
        if (! $this->usesMysql()) {
            return false;
        }

        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', 'client_assessments')
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
