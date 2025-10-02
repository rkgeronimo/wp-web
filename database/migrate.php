#!/usr/bin/env php
<?php

/**
 * Database Migration CLI Tool
 *
 * Usage:
 *   php database/migrate.php migrate           - Run all pending migrations
 *   php database/migrate.php rollback          - Rollback the last batch
 *   php database/migrate.php rollback --steps=2 - Rollback last 2 batches
 *   php database/migrate.php status            - Show migration status
 *   php database/migrate.php create <name>     - Create a new migration file
 */

// Load WordPress
define('WP_USE_THEMES', false);
$wp_load_path = __DIR__ . '/../web/wp/wp-load.php';

if (!file_exists($wp_load_path)) {
    echo "Error: WordPress not found. Expected at: {$wp_load_path}\n";
    exit(1);
}

require_once $wp_load_path;
require_once __DIR__ . '/MigrationManager.php';

use Geronimo\Database\MigrationManager;

// Parse command line arguments
$command = $argv[1] ?? 'help';
$options = parseOptions(array_slice($argv, 2));

$manager = new MigrationManager(__DIR__ . '/migrations');

switch ($command) {
    case 'migrate':
        echo "Running migrations...\n";
        $result = $manager->migrate();
        printResult($result);
        exit($result['status'] === 'success' ? 0 : 1);

    case 'rollback':
        $steps = isset($options['steps']) ? (int)$options['steps'] : 1;
        echo "Rolling back {$steps} batch(es)...\n";
        $result = $manager->rollback($steps);
        printResult($result);
        exit($result['status'] === 'success' ? 0 : 1);

    case 'status':
        echo "Migration Status:\n";
        echo str_repeat('-', 80) . "\n";
        $status = $manager->status();

        if (empty($status)) {
            echo "No migrations found.\n";
        } else {
            foreach ($status as $item) {
                $executed = $item['executed'] ? '✓' : '✗';
                $batch = $item['batch'] ? "(batch {$item['batch']})" : '';
                printf("%s %s %s\n", $executed, $item['migration'], $batch);
            }
        }
        echo str_repeat('-', 80) . "\n";
        exit(0);

    case 'create':
        if (!isset($argv[2])) {
            echo "Error: Migration name required.\n";
            echo "Usage: php database/migrate.php create <migration_name>\n";
            exit(1);
        }

        $name = $argv[2];
        $result = createMigration($name);
        echo $result['message'] . "\n";
        exit($result['status'] === 'success' ? 0 : 1);

    case 'help':
    default:
        printHelp();
        exit(0);
}

/**
 * Parse command line options
 */
function parseOptions($args)
{
    $options = [];
    foreach ($args as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
        }
    }
    return $options;
}

/**
 * Print command result
 */
function printResult($result)
{
    echo "{$result['message']}\n";

    if (!empty($result['executed'])) {
        echo "\nExecuted:\n";
        foreach ($result['executed'] as $migration) {
            echo "  ✓ {$migration}\n";
        }
    }

    if (!empty($result['rolledBack'])) {
        echo "\nRolled back:\n";
        foreach ($result['rolledBack'] as $migration) {
            echo "  ✓ {$migration}\n";
        }
    }

    if (!empty($result['errors'])) {
        echo "\nErrors:\n";
        foreach ($result['errors'] as $error) {
            echo "  ✗ {$error['migration']}: {$error['error']}\n";
        }
    }
}

/**
 * Create a new migration file
 */
function createMigration($name)
{
    $migrationsPath = __DIR__ . '/migrations';

    if (!is_dir($migrationsPath)) {
        mkdir($migrationsPath, 0755, true);
    }

    // Generate timestamp and filename
    $timestamp = date('Y_m_d_His');
    $filename = "{$timestamp}_{$name}.php";
    $filepath = $migrationsPath . '/' . $filename;

    // Create migration template
    $template = <<<'PHP'
<?php

/**
 * Migration: {NAME}
 * Created: {DATE}
 */

return [
    'up' => function($wpdb) {
        // Add your migration code here
        // Example:
        // $wpdb->query("CREATE TABLE ...");
        // $wpdb->query("ALTER TABLE ...");
        // $wpdb->query("INSERT INTO ...");
    },

    'down' => function($wpdb) {
        // Add your rollback code here
        // Example:
        // $wpdb->query("DROP TABLE ...");
        // $wpdb->query("DELETE FROM ...");
    }
];

PHP;

    $template = str_replace('{NAME}', $name, $template);
    $template = str_replace('{DATE}', date('Y-m-d H:i:s'), $template);

    file_put_contents($filepath, $template);

    return [
        'status' => 'success',
        'message' => "Created migration: {$filename}",
        'file' => $filepath
    ];
}

/**
 * Print help message
 */
function printHelp()
{
    echo <<<HELP
Database Migration Tool for Geronimo

Usage:
  php database/migrate.php <command> [options]

Commands:
  migrate              Run all pending migrations
  rollback             Rollback the last batch of migrations
  rollback --steps=N   Rollback the last N batches
  status               Show status of all migrations
  create <name>        Create a new migration file
  help                 Show this help message

Examples:
  php database/migrate.php migrate
  php database/migrate.php rollback
  php database/migrate.php rollback --steps=2
  php database/migrate.php status
  php database/migrate.php create add_user_preferences_table

Migration File Naming:
  Files must follow the pattern: YYYY_MM_DD_HHMMSS_description.(sql|php)
  Examples:
    - 2024_01_01_000000_initial_schema.sql
    - 2024_01_15_143022_add_courses_table.php

HELP;
}
