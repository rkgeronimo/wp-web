<?php

/**
 * Add Reservation Status Improvements
 *
 * This migration updates the wp_rkg_excursion_gear table to support:
 * - Enhanced status field with proper states (pending, active, completed, deleted)
 * - Soft delete functionality for reservations
 * - Better filtering and management of reservations
 *
 * Status values:
 * 0 = Pending (Created but no equipment issued yet)
 * 1 = Active (Equipment issued, waiting return)
 * 2 = Completed (All equipment returned)
 * 3 = Deleted (Soft deleted - hidden from default view)
 */

return [
    /**
     * Run the migration (up)
     */
    'up' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_gear';

        // Note: The 'state' column already exists as tinyint(1)
        // This migration ensures it can hold values 0-3 and adds a comment
        $wpdb->query("
            ALTER TABLE {$tableName}
            MODIFY COLUMN `state` tinyint(1) DEFAULT 0
            COMMENT 'Reservation status: 0=Pending, 1=Active, 2=Completed, 3=Deleted'
        ");

        // Add index on state column for better query performance
        $wpdb->query("
            ALTER TABLE {$tableName}
            ADD INDEX idx_state (state)
        ");

        // Add deleted_at timestamp column to track when reservation was deleted
        $wpdb->query("
            ALTER TABLE {$tableName}
            ADD COLUMN deleted_at datetime NULL DEFAULT NULL
            COMMENT 'Timestamp when reservation was soft deleted'
        ");

        // Update existing reservations to have proper status based on current state
        // If all equipment is returned, mark as completed (state=2)
        // This is a conservative approach, we check if reservation has equipment assigned
        // and if all assigned equipment is marked as returned

        // Note: There is no auto-update of existing records in this
        // migration to avoid unintended changes to historical data
        // Admins can manually update status if needed
    },

    /**
     * Reverse the migration (down)
     */
    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'rkg_excursion_gear';

        // Remove the index
        $wpdb->query("ALTER TABLE {$tableName} DROP INDEX IF EXISTS idx_state");

        // Remove the deleted_at column
        $wpdb->query("ALTER TABLE {$tableName} DROP COLUMN IF EXISTS deleted_at");

        // Restore state column to original definition (remove comment)
        $wpdb->query("
            ALTER TABLE {$tableName}
            MODIFY COLUMN `state` tinyint(1)
        ");
    }
];
