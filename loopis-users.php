<?php
/**
* Plugin Name:  LOOPIS Users
* Plugin URI:   https://github.com/LOOPIS-app/loopis-users
* Description:  Plugin for configuring user management in LOOPIS.app
* Version:      0.04
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
define('LOOPIS_USERS_VERSION', '0.04');

// Include files always loaded
include_once LOOPIS_USERS_DIR . '/includes/filters/loopis-user-avatars.php';
include_once LOOPIS_USERS_DIR . '/includes/filters/loopis-user-roles.php';

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
    include_once LOOPIS_USERS_DIR . '/comment-mention/loopis_user_mention.php';
}

add_action('rest_api_init', function () {
    register_rest_route('loopis-users/v1', '/user-logins', [
        'methods'  => WP_REST_Server::READABLE,

        'permission_callback' => function () {
            return is_user_logged_in();
        },

        'callback' => function () {
            $users = get_users([
                'blog_id' => get_current_blog_id(),
                'exclude' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'fields' => ['ID', 'user_login'],
                'orderby' => 'user_login',
                'order' => 'ASC',
            ]);

            $result = array_map(function ($user) {
                return [
                    'id'    => (int) $user->ID,
                    'login' => $user->user_login,
                ];
            }, $users);
            array_unshift($result, [
                'id'    => 0,
                'login' => 'admins',
            ]);

            return rest_ensure_response($result);
        },
    ]);
});

add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !comments_open()) {
        return;
    }
    wp_register_script(
        'loopis-users-mentions',
        LOOPIS_USERS_URL . '/assets/js/CM.js',
        [],
        LOOPIS_USERS_VERSION,
        true
    );

    wp_localize_script('loopis-users-mentions', 'loopisUserData', [
        'restUrl' => esc_url_raw(rest_url('loopis-users/v1/user-logins')),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);

    wp_enqueue_script('loopis-users-mentions');
});
  



function loopis_mention_mail(string $comment_content, int $comment_id, int $sender_id, int $recipient_id){
    loopis_get_mail_templates();

    $sender = get_userdata($sender_id);
    $sender_name = $sender ? $sender->display_name : '';

    $recipient = get_userdata($recipient_id);
    $to = $recipient ? $recipient->user_email : '';

    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    $post_title = get_the_title($comment->comment_post_ID);
    $post_link = get_permalink( $comment->comment_post_ID );

    $comment_content = preg_replace('/\t+/', '', $comment_content); // Remove all tabs
    $comment_content = preg_replace('/\n\s+/', "\n", $comment_content); // Remove leading spaces on new lines
    $subject = "🔔 {$post_title}";
    $headers = loopis_mail_headers();
    $outro = array(loopis_mail_ping($sender_name, $post_link, $post_title));

    $the_email = loopis_mail_template('',$outro, $comment_content,'Du är omnämnd i en kommentar:');
    $the_email .= loopis_mail_footer('Gå till LOOPIS.app för att hantera annonsen eller skriva ett svar.');


    wp_mail($to, $subject, $the_email, $headers); 
}




add_action('comment_post', 'loopis_process_comment_mentions', 10, 3);

function loopis_process_comment_mentions($comment_id, $comment_approved, $comment_data) {
    if ((string) $comment_approved !== '1') {
        return;
    }

    $content = $comment_data['comment_content'] ?? '';

    preg_match_all(
        '/(?<![A-Za-z0-9_.-])@([A-Za-z0-9_.-]+)/',
        $content,
        $matches
    );

    if (empty($matches[1])) {
        return;
    }

    $users = [];

    foreach (array_unique($matches[1]) as $login) {

        if (strtolower($login) === 'admins'){
            $manager_users = get_users(array(
		        'role__in' => ['manager'],
                'blog_id'  => get_current_blog_id(),
		        'fields' => 'all'
	        ));    
            foreach ($manager_users as $user) {
                if ($user instanceof WP_User) {
                    $users[$user->ID] = $user;
                }
            }
            
        }else{
            $user = get_user_by('login', $login);
            if ($user instanceof WP_User) {
                $users[$user->ID] = $user;
            }
        }
    }

    if (!$users) {
        return;
    }

    $user_ids = array_map('intval', array_keys($users));

    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    foreach ($users as $user) {
        if ((int) $user->ID === (int) $comment->user_id) {
            continue;
        }
        loopis_mention_mail($content, $comment_id, $comment->user_id, $user->ID);
    }
}
