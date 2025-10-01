<?php

namespace Geronimo\Database;

/**
 * Database Migration Manager
 *
 * Manages database schema migrations for the Geronimo project.
 */
class MigrationManager
{
    private $db;
    private $migrationsTable = 'schema_migrations';
    private $migrationsPath;

    public function __construct($migrationsPath = null)
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->migrationsPath = $migrationsPath ?: __DIR__ . '/migrations';
        $this->ensureMigrationsTableExists();
    }

    /**
     * Create the migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTableExists()
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $charset = $this->db->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY migration_unique (migration)
        ) {$charset}";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Run all pending migrations
     */
    public function migrate()
    {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            return ['status' => 'success', 'message' => 'No pending migrations', 'count' => 0];
        }

        $batch = $this->getNextBatchNumber();
        $executed = [];
        $errors = [];

        foreach ($migrations as $migration) {
            try {
                $this->runMigration($migration, $batch);
                $executed[] = $migration;
            } catch (\Exception $e) {
                $errors[] = [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ];
                break; // Stop on first error
            }
        }

        return [
            'status' => empty($errors) ? 'success' : 'error',
            'message' => count($executed) . ' migrations executed',
            'executed' => $executed,
            'errors' => $errors,
            'count' => count($executed)
        ];
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback($steps = 1)
    {
        $batches = $this->getMigratedBatches();

        if (empty($batches)) {
            return ['status' => 'success', 'message' => 'No migrations to rollback', 'count' => 0];
        }

        $targetBatches = array_slice($batches, 0, $steps);
        $migrations = $this->getMigrationsByBatches($targetBatches);
        $rolledBack = [];
        $errors = [];

        // Rollback in reverse order
        foreach (array_reverse($migrations) as $migration) {
            try {
                $this->rollbackMigration($migration);
                $rolledBack[] = $migration;
            } catch (\Exception $e) {
                $errors[] = [
                    'migration' => $migration,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'status' => empty($errors) ? 'success' : 'error',
            'message' => count($rolledBack) . ' migrations rolled back',
            'rolledBack' => $rolledBack,
            'errors' => $errors,
            'count' => count($rolledBack)
        ];
    }

    /**
     * Get migration status
     */
    public function status()
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $status = [];
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'executed' => in_array($migration, $executed),
                'batch' => $this->getMigrationBatch($migration)
            ];
        }

        return $status;
    }

    /**
     * Run a single migration
     */
    private function runMigration($migrationFile, $batch)
    {
        $migrationPath = $this->migrationsPath . '/' . $migrationFile;

        if (!file_exists($migrationPath)) {
            throw new \Exception("Migration file not found: {$migrationFile}");
        }

        // For SQL files
        if (substr($migrationFile, -4) === '.sql') {
            $sql = file_get_contents($migrationPath);
            $this->executeSqlFile($sql);
        }
        // For PHP files
        else if (substr($migrationFile, -4) === '.php') {
            $migration = include $migrationPath;

            if (is_array($migration) && isset($migration['up'])) {
                if (is_callable($migration['up'])) {
                    $migration['up']($this->db);
                }
            } else if (is_callable($migration)) {
                $migration($this->db);
            } else {
                throw new \Exception("Invalid migration format in {$migrationFile}");
            }
        }

        $this->recordMigration($migrationFile, $batch);
    }

    /**
     * Rollback a single migration
     */
    private function rollbackMigration($migrationFile)
    {
        $migrationPath = $this->migrationsPath . '/' . $migrationFile;

        if (!file_exists($migrationPath)) {
            throw new \Exception("Migration file not found: {$migrationFile}");
        }

        // For PHP files with down method
        if (substr($migrationFile, -4) === '.php') {
            $migration = include $migrationPath;

            if (is_array($migration) && isset($migration['down']) && is_callable($migration['down'])) {
                $migration['down']($this->db);
            } else {
                throw new \Exception("No rollback method defined for {$migrationFile}");
            }
        } else {
            throw new \Exception("Cannot rollback SQL-only migration: {$migrationFile}");
        }

        $this->removeMigrationRecord($migrationFile);
    }

    /**
     * Execute SQL file contents
     */
    private function executeSqlFile($sql)
    {
        // Split by semicolon, but be careful with stored procedures
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $result = $this->db->query($statement);
                if ($result === false) {
                    throw new \Exception("SQL Error: " . $this->db->last_error);
                }
            }
        }
    }

    /**
     * Get all migration files from the migrations directory
     */
    private function getAllMigrationFiles()
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.(sql|php)$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get migrations that haven't been executed yet
     */
    private function getPendingMigrations()
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();
        return array_diff($all, $executed);
    }

    /**
     * Get list of executed migrations
     */
    private function getExecutedMigrations()
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $results = $this->db->get_col("SELECT migration FROM {$tableName} ORDER BY id ASC");
        return $results ?: [];
    }

    /**
     * Get the next batch number
     */
    private function getNextBatchNumber()
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $batch = $this->db->get_var("SELECT MAX(batch) FROM {$tableName}");
        return ($batch ?: 0) + 1;
    }

    /**
     * Get list of batches (most recent first)
     */
    private function getMigratedBatches()
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $batches = $this->db->get_col("SELECT DISTINCT batch FROM {$tableName} ORDER BY batch DESC");
        return $batches ?: [];
    }

    /**
     * Get migrations for specific batches
     */
    private function getMigrationsByBatches($batches)
    {
        if (empty($batches)) {
            return [];
        }

        $tableName = $this->db->prefix . $this->migrationsTable;
        $placeholders = implode(',', array_fill(0, count($batches), '%d'));
        $sql = $this->db->prepare("SELECT migration FROM {$tableName} WHERE batch IN ({$placeholders})", $batches);
        return $this->db->get_col($sql) ?: [];
    }

    /**
     * Get batch number for a migration
     */
    private function getMigrationBatch($migration)
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        return $this->db->get_var($this->db->prepare(
            "SELECT batch FROM {$tableName} WHERE migration = %s",
            $migration
        ));
    }

    /**
     * Record that a migration has been executed
     */
    private function recordMigration($migration, $batch)
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $this->db->insert($tableName, [
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord($migration)
    {
        $tableName = $this->db->prefix . $this->migrationsTable;
        $this->db->delete($tableName, ['migration' => $migration]);
    }
}
