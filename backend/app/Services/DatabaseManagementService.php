<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseManagementService
{
    /**
     * Get all database tables.
     */
    public function getAllTables(): array
    {
        $tables = DB::select("SHOW TABLES");

        // Extract table names from the result
        // The property name is like "Tables_in_your_database"
        $tableNames = [];
        foreach ($tables as $table) {
            $array = (array) $table;
            $tableNames[] = array_values($array)[0];
        }

        return $tableNames;
    }

    /**
     * Get information about a specific table.
     */
    public function getTableInfo(string $tableName): array
    {
        $result = DB::select("SELECT
            TABLE_NAME as name,
            TABLE_ROWS as rows,
            ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ", [DB::getDatabaseName(), $tableName]);

        if (empty($result)) {
            return [
                'name' => $tableName,
                'rows' => 0,
                'size_mb' => 0,
                'protected' => $this->isProtectedTable($tableName),
            ];
        }

        $table = (array) $result[0];
        $table['protected'] = $this->isProtectedTable($tableName);

        return $table;
    }

    /**
     * Check if a table is protected and cannot be emptied.
     */
    public function isProtectedTable(string $tableName): bool
    {
        $protectedTables = [
            'users',
            'migrations',
            'settings',
            'failed_jobs',
            'jobs',
            'cache',
            'sessions',
            'telescope_sessions',
            'telescope_entries',
            'telescope_monitoring',
        ];

        return in_array($tableName, $protectedTables);
    }

    /**
     * Truncate a table (empty all data).
     */
    public function truncateTable(string $tableName): void
    {
        if ($this->isProtectedTable($tableName)) {
            throw new \Exception("Cannot truncate protected table: {$tableName}");
        }

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate the table
        DB::table($tableName)->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Get all tables with their information.
     */
    public function getAllTablesWithInfo(): array
    {
        $tables = $this->getAllTables();
        $tableInfo = [];

        foreach ($tables as $tableName) {
            $tableInfo[] = $this->getTableInfo($tableName);
        }

        return $tableInfo;
    }
}
