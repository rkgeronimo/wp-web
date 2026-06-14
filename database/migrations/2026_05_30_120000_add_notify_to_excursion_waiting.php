<?php

/**
 * Migration: add_notify_to_excursion_waiting
 * Created: 2026-05-30 12:00:00
 *
 * Adds a 'notify' column to the rkg_excursion_waiting table
 * so users can opt in to email notification when a spot
 * becomes available on a full excursion.
 */

return [
    'up' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_waiting';

        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'notify'",
                DB_NAME,
                $tableName
            )
        );

        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE {$tableName}
                ADD COLUMN notify tinyint(1) DEFAULT 0
                AFTER post_id"
            );
        }
    },

    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_waiting';

        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'notify'",
                DB_NAME,
                $tableName
            )
        );

        if (!empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN notify");
        }
    }
];
