<?php

/**
 * Add course column to excursion_meta table
 *
 * This migration adds a 'course' column to the wp_rkg_excursion_meta table
 * to support reserving excursion spots for specific course participants.
 */

return [
    /**
     * Run the migration (up)
     *
     * Adds the 'course' column to wp_rkg_excursion_meta table
     */
    'up' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_meta';

        // Check if column already exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'course'",
                DB_NAME,
                $tableName
            )
        );

        if (empty($column_exists)) {
            // Add course column after deadline column
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD COLUMN course mediumint DEFAULT NULL
                AFTER deadline"
            );
        }
    },

    /**
     * Reverse the migration (down)
     *
     * Removes the 'course' column from wp_rkg_excursion_meta table
     */
    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_meta';

        // Check if column exists before trying to drop it
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'course'",
                DB_NAME,
                $tableName
            )
        );

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN course");
        }
    }
];
