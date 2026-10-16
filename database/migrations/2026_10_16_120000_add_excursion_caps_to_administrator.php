<?php

/**
 * Add excursion capabilities to the administrator role
 *
 * Administrators were missing `edit_excursions` and `edit_other_excursions`,
 * so they could not see the excursion list or excursions created by other
 * users. Roles are stored in the database and Activator only sets them on
 * plugin activation, so existing installs need this migration.
 */

return [
    /**
     * Run the migration (up)
     */
    'up' => function($wpdb) {
        $role = get_role('administrator');

        if (!$role) {
            throw new \Exception('Role "administrator" does not exist');
        }

        // add_cap() overwrites the value, so a re-run does not fail
        $role->add_cap('edit_excursions');
        $role->add_cap('edit_other_excursions');
    },

    /**
     * Reverse the migration (down)
     */
    'down' => function($wpdb) {
        $role = get_role('administrator');

        if ($role) {
            $role->remove_cap('edit_excursions');
            $role->remove_cap('edit_other_excursions');
        }
    }
];
