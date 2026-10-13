<?php

/**
 * Add donation amount to equipment reservations
 *
 * Adds `donation_amount` to the wp_rkg_excursion_gear table. NULL means
 * there is no donation for the reservation.
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
                AND COLUMN_NAME = 'donation_amount'",
                DB_NAME,
                $tableName
            )
        );

        if (empty($column)) {
            $wpdb->query("
                ALTER TABLE {$tableName}
                ADD COLUMN donation_amount DECIMAL(10,2) NULL DEFAULT NULL
                COMMENT 'Donation amount in EUR, NULL when there is no donation'
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
                AND COLUMN_NAME = 'donation_amount'",
                DB_NAME,
                $tableName
            )
        );

        if (!empty($column)) {
            $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN donation_amount");
        }
    }
];
