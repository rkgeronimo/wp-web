<?php

/**
 * Add archived flag to equipment reservations
 *
 * Adds `archived` to the wp_rkg_excursion_gear table. It is a separate flag
 * from `state`, so archiving a reservation does not change its status and
 * inventory status changes do not affect it.
 */

return [
    /**
     * Run the migration (up)
     */
    'up' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_gear';

        // Skip when the column already exists so a re-run does not fail
        $column = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'archived'",
                DB_NAME,
                $tableName
            )
        );

        if (empty($column)) {
            $wpdb->query("
                ALTER TABLE {$tableName}
                ADD COLUMN archived BOOLEAN NOT NULL DEFAULT 0
                COMMENT 'Reservation is archived (1) or not (0)'
            ");
        }
    },

    /**
     * Reverse the migration (down)
     */
    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_gear';

        // Check if column exists before trying to drop it. MySQL has no
        // DROP COLUMN IF EXISTS
        $column = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'archived'",
                DB_NAME,
                $tableName
            )
        );

        if (!empty($column)) {
            $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN archived");
        }
    }
];
