<?php
/**
* Plugin Name:  LOOPIS Users
* Plugin URI:   https://github.com/LOOPIS-app/loopis-users
* Description:  Plugin for configuring user management in LOOPIS.app
* Version:      0.03
* Author:       The Develoopers
* Author URI:   https://loopis.org
* License:      GPL-3.0-or-later
* License URI:  https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain:  loopis-users
*/

/*
 * Copyright (C) 2026 LOOPIS
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Define plugin version
define('LOOPIS_USERS_VERSION', '0.03');

// Define plugin folder path constants
define('LOOPIS_USERS_DIR', plugin_dir_path(__FILE__)); // Server-side path to /wp-content/plugins/loopis-users/
define('LOOPIS_USERS_URL', plugin_dir_url(__FILE__));  // Client-side path to https://site.com/wp-content/plugins/loopis-users/

// Include necessary files
include_once LOOPIS_USERS_DIR . 'filters/loopis_user_avatars.php';
include_once LOOPIS_USERS_DIR . 'filters/loopis_user_roles.php';
include_once LOOPIS_USERS_DIR . 'pn_map/loopis_postnum_map.php';

/*
 * ========================================================
 *                     TESTING GROUND
 * ========================================================
 */

add_action('admin_menu', function () {
    add_menu_page(
        'CM-test',
        'CM-test',
        '',
        'CM-test',
        'CM_test_page',
        'dashicons-database-import',
        80
    );
});

function CM_test_page(){
    include_once LOOPIS_USERS_DIR . 'comment-mention/loopis_user_mention.php';
}




add_action('rest_api_init', function () {
    register_rest_route('my-plugin/v1', '/user-logins', [
        'methods'  => WP_REST_Server::READABLE,

        // Authorization—not just authentication.
        'permission_callback' => function () {
            return current_user_can('list_users');
        },

        'callback' => function () {
            $users = get_users([
                'fields' => ['ID', 'user_login'],
                'orderby' => 'ID',
                'order' => 'ASC',
            ]);

            $result = array_map(function ($user) {
                return [
                    'id'    => (int) $user->ID,
                    'login' => $user->user_login,
                ];
            }, $users);

            return rest_ensure_response($result);
        },
    ]);
});


add_action('admin_enqueue_scripts', function ($hook) {
    wp_enqueue_script(
        'my-plugin-admin',
        plugin_dir_url(__FILE__) . 'admin.js',
        ['wp-api'],
        '1.0.0',
        true
    );

    wp_localize_script('my-plugin-admin', 'MyPluginData', [
        'restUrl' => esc_url_raw(rest_url('my-plugin/v1/user-logins')),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);
});