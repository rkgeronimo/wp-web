<?php
namespace RKGTheme\Admin;

use Timber;
use WP_User;

/**
 * Class: UsersList
 *
 * Promjena zadanog prikaza korisnika u admin sučelju
 *
 * @author Adrijan Adanić <adanic.ado@gmail.com>
 *
 */
class UsersList
{
    /**
     * init
     *
     * Inicijalizacija potrebnih akcija i filtara
     *
     * @return void
     */
    public function init()
    {
        add_filter('manage_users_columns', array($this, 'removeUsersColumns'));
        add_filter('manage_users_columns', array($this, 'addUsersColumns'));
        add_filter(
            'manage_users_custom_column',
            array($this, 'addColumnValue'),
            10,
            3
        );
        add_filter('bulk_actions-users', array($this, 'paySubscription'));
        add_filter(
            'handle_bulk_actions-users',
            array($this, 'paySubscriptionSave'),
            10,
            3
        );

        add_action('restrict_manage_users', array($this, 'changeRoles'));
        add_action('admin_init', array($this, 'changeRolesSave'));

        add_filter('user_row_actions', array($this, 'changeUserRowActions'), 10, 2);
    }

    /**
     * removeUsersColumns
     *
     * @param array $columnHeaders
     *
     * @return array
     */
    public function removeUsersColumns($columnHeaders)
    {
        unset($columnHeaders['email']);
        unset($columnHeaders['posts']);

        return $columnHeaders;
    }

    /**
     * addUsersColumns
     *
     * @param array $columnHeaders
     *
     * @return array
     */
    public function addUsersColumns($columnHeaders)
    {
        $columnHeaders['memberNumber'] = "Članski broj";
        $columnHeaders['oib']          = "OIB";
        $columnHeaders['currnetYear']  = "Članarina za ".date('Y');
        $columnHeaders['nextYear']     = "Članarina za ".(date('Y')+1);
        $columnHeaders['firstYear']    = "Član od";
        $columnHeaders['lastYear']     = "Zadnja aktivna godina";

        return $columnHeaders;
    }

    /**
     * addColumnValue
     *
     * @param mixed $val
     * @param mixed $columnName
     * @param mixed $userId
     *
     * @SuppressWarnings("unused")
     *
     * @return string
     */
    public function addColumnValue($val, $columnName, $userId)
    {
        global $wpdb;
        $number = null;
        $oib = null;
        $year   = null;
        $payed  = null;
        switch ($columnName) {
            case 'memberNumber':
                $number = get_user_meta(
                    $userId,
                    'memberNumber',
                    true
                );
                break;
            case 'oib':
                $oib = get_user_meta(
                    $userId,
                    'oib',
                    true
                );
                break;
            case 'currnetYear':
                $tableName = $wpdb->prefix."rkg_member_subscription";
                $payed     = $wpdb->get_row(
                    "SELECT * FROM "
                    .$tableName
                    ." WHERE user = ".$userId." AND year = ".date("Y")
                );
                break;
            case 'nextYear':
                $tableName = $wpdb->prefix."rkg_member_subscription";
                $payed     = $wpdb->get_row(
                    "SELECT * FROM "
                    .$tableName
                    ." WHERE user = ".$userId." AND year = ".(date("Y")+1)
                );
                break;
            case 'firstYear':
                $tableName = $wpdb->prefix."rkg_member_subscription";
                $year      = $wpdb->get_row(
                    "SELECT * FROM "
                    .$tableName
                    ." WHERE user = ".$userId
                    ." ORDER BY  year ASC"
                );
                break;
            case 'lastYear':
                $tableName = $wpdb->prefix."rkg_member_subscription";
                $year      = $wpdb->get_row(
                    "SELECT * FROM "
                    .$tableName
                    ." WHERE user = ".$userId
                    ." ORDER BY  year DESC"
                );
                break;
        }

        if ($payed) {
            return '<span class="dashicons dashicons-yes"></span>';
        }

        if ($year) {
            return $year->year;
        }

        if ($number) {
            return $number;
        }

        if ($oib) {
            return $oib;
        }

        return "&nbsp;";
    }

    /**
     * paySubscription
     *
     * @param array $bulkActions
     *
     * @return array
     */
    public function paySubscription($bulkActions)
    {
        $bulkActions['payCurrent'] = __(
            'Plaćena članarina za '.date("Y"),
            'payCurrent'
        );
        $bulkActions['payNext']    = __(
            'Plaćena članarina za '.(date("Y")+1),
            'payNext'
        );
        $bulkActions['payCurrentNot'] = __(
            'Nije plaćena članarina za '.date("Y"),
            'payCurrent'
        );
        $bulkActions['payNextNot']    = __(
            'Nije plaćena članarina za '.(date("Y")+1),
            'payNext'
        );
        $bulkActions['setHonorMember']    = __(
            'Proglasi počasnog člana',
            'setHonorMember'
        );

        return $bulkActions;
    }

    /**
     * paySubscriptionSave
     *
     * @param string $redirectTo
     * @param string $doaction
     * @param array  $userIds
     *
     * @return string
     */
    public function paySubscriptionSave($redirectTo, $doaction, $userIds)
    {
        global $wpdb;
        $tableName = $wpdb->prefix."rkg_member_subscription";
        if ('payCurrent' === $doaction) {
            foreach ($userIds as $userId) {
                $wpdb->replace(
                    $tableName,
                    array(
                        'user'    => $userId,
                        'year'    => date("Y"),
                    )
                );

                $user = new WP_User($userId);
                $user->add_role('member');
                $user->remove_role('user');

                $memberNumber = get_user_meta(
                    $userId,
                    'memberNumber',
                    true
                );

                if (empty($memberNumber)) {
                    for ($i = 1; $i < 999; $i++) {
                        $tempNumber =
                            str_pad($i, 2, "0", STR_PAD_LEFT).'/'.date('y');
                        $members    = get_users(array(
                            'meta_key' => 'memberNumber',
                            'meta_value' => $tempNumber,
                        ));
                        if (empty($members)) {
                            add_user_meta(
                                $userId,
                                'memberNumber',
                                $tempNumber,
                                true
                            );
                            break;
                        };
                    }

                }

            }
            $redirectTo = add_query_arg(
                'bulk_users_processed',
                count($userIds),
                $redirectTo
            );

            return $redirectTo;
        } elseif ('payCurrentNot' === $doaction) {
            foreach ($userIds as $userId) {
                $wpdb->delete(
                    $tableName,
                    array(
                        'user'    => $userId,
                        'year'    => date("Y"),
                    )
                );

                $user = new WP_User($userId);
                $user->set_role('user');
            }
            $redirectTo = add_query_arg(
                'bulk_users_processed',
                count($userIds),
                $redirectTo
            );

            return $redirectTo;
        } elseif ('payNext' === $doaction) {
            foreach ($userIds as $userId) {
                $wpdb->replace(
                    $tableName,
                    array(
                        'user'    => $userId,
                        'year'    => (date("Y")+1),
                    )
                );
            }
            $redirectTo = add_query_arg(
                'bulk_users_processed',
                count($userIds),
                $redirectTo
            );

            return $redirectTo;
        } elseif ('payNextNot' === $doaction) {
            foreach ($userIds as $userId) {
                $wpdb->delete(
                    $tableName,
                    array(
                        'user'    => $userId,
                        'year'    => (date("Y")+1),
                    )
                );
            }
            $redirectTo = add_query_arg(
                'bulk_users_processed',
                count($userIds),
                $redirectTo
            );

            return $redirectTo;
        } elseif ('setHonorMember' === $doaction) {
            foreach ($userIds as $userId) {
                $user = new WP_User($userId);
                $user->set_role('honorMember');
            }
            $redirectTo = add_query_arg(
                'bulk_users_processed',
                count($userIds),
                $redirectTo
            );

            return $redirectTo;
        }

        return $redirectTo;
    }

    /**
     * changeRoles
     *
     * @return void
     */
    public function changeRoles($which)
    {
        if (!current_user_can('promote_users')) {
            return;
        }

        global $wp_roles;

        $context = Timber::get_context();
        $context['which'] = $which;

        if (!empty($context['request']->get['rkg_add_role_submit_'.$which])
            && !empty($context['request']->get['rkg_add_role_'.$which])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->add_role($context['request']->get['rkg_add_role_'.$which]);
                // $user->remove_role('user');
            }
        }

        if (!empty($context['request']->get['rkg_revoke_role_submit_'.$which])
            && !empty($context['request']->get['rkg_add_role_'.$which])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->remove_role($context['request']->get['rkg_add_role_'.$which]);
            }
        }

        $context['roleEditList'] = $wp_roles->role_names;
        unset($context['roleEditList']['honorMember']);
        unset($context['roleEditList']['user']);
        unset($context['roleEditList']['member']);
        $templates               = array('userRolesEdit.twig');
        Timber::render($templates, $context);
    }

    public function changeRolesSave()
    {
        $context = Timber::get_context();
        $url = get_admin_url();

        if (!empty($context['request']->get['rkg_add_role_submit_top'])
            && !empty($context['request']->get['rkg_add_role_top'])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->add_role($context['request']->get['rkg_add_role_top']);
                // $user->remove_role('user');
            }
            header("Location: ".$url."users.php");
            die();
        }

        if (!empty($context['request']->get['rkg_revoke_role_submit_top'])
            && !empty($context['request']->get['rkg_add_role_top'])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->remove_role($context['request']->get['rkg_add_role_top']);
            }
            header("Location: ".$url."users.php");
            die();
        }

        if (!empty($context['request']->get['rkg_add_role_submit_bottom'])
            && !empty($context['request']->get['rkg_add_role_bottom'])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->add_role($context['request']->get['rkg_add_role_bottom']);
                // $user->remove_role('user');
            }
            header("Location: ".$url."users.php");
            die();
        }

        if (!empty($context['request']->get['rkg_revoke_role_submit_bottom'])
            && !empty($context['request']->get['rkg_add_role_bottom'])) {
            foreach ($context['request']->get['users'] as $userId) {
                $user = new WP_User($userId);
                $user->remove_role($context['request']->get['rkg_add_role_bottom']);
            }
            header("Location: ".$url."users.php");
            die();
        }
    }

    /**
     * changeUserRowActions
     *
     * @param mixed $actions
     * @param mixed $userObject
     *
     * @return array
     */
    public function changeUserRowActions($actions, $userObject)
    {
        unset($actions['delete']);
        unset($actions['view']);

        return $actions;
    }
}
