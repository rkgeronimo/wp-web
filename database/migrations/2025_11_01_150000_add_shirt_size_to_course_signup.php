<?php

/**
 * Add shirt_size column to course_signup table
 *
 * This migration adds a 'shirt_size' column to the wp_rkg_course_signup table
 * to store user's shirt size when signing up for R1 courses.
 * Useful for both BCD and T-shirt sizing.
 */

return [
    /**
     * Run the migration (up)
     *
     * Adds the 'shirt_size' column to wp_rkg_course_signup table
     */
    'up' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_course_signup';

        // Check if column already exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'shirt_size'",
                DB_NAME,
                $tableName
            )
        );

        if (empty($column_exists)) {
            // Add shirt_size column after shoe_size column
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD COLUMN shirt_size text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL
                AFTER shoe_size"
            );
        }
    },

    /**
     * Reverse the migration (down)
     *
     * Removes the 'shirt_size' column from wp_rkg_course_signup table
     */
    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_course_signup';

        // Check if column exists before trying to drop it
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'shirt_size'",
                DB_NAME,
                $tableName
            )
        );

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN shirt_size");
        }
    }
];
