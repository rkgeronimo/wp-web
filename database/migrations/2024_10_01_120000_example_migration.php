<?php

/**
 * Example Migration
 *
 * This is an example migration file showing how to create PHP-based migrations.
 * You can delete this file once you understand the structure.
 *
 * To create a new migration, run:
 *   php database/migrate.php create your_migration_name
 */

return [
    /**
     * Run the migration (up)
     *
     * This method is executed when running: php database/migrate.php migrate
     */
    'up' => function($wpdb) {
        $charset = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'example_table';

        // Example: Create a new table
        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            status varchar(50) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status_idx (status)
        ) {$charset}";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Example: Insert some default data
        // $wpdb->insert($tableName, [
        //     'name' => 'Example Entry',
        //     'description' => 'This is an example'
        // ]);

        // Example: Modify existing table
        // $wpdb->query("ALTER TABLE {$wpdb->prefix}posts ADD COLUMN custom_field VARCHAR(255)");
    },

    /**
     * Reverse the migration (down)
     *
     * This method is executed when running: php database/migrate.php rollback
     * It should undo whatever the 'up' method does.
     */
    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'example_table';

        // Drop the table created in the up method
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");

        // Example: Remove column added in up method
        // $wpdb->query("ALTER TABLE {$wpdb->prefix}posts DROP COLUMN custom_field");
    }
];
