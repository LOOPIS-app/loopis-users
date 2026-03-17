<?php
/**
 * Function to replace user avatars with loopis avatars.
 * 
 * @package LOOPIS_Users
 * @subpackage User_Avatars
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

add_filter('get_avatar', 'loopis_avatars', 10, 5);

/**
 * Redirect get avatar to local image.
 * 
 * @return string HTML output url
 */
function loopis_avatars($avatar, $id_or_email, $size, $default, $alt) {

    $user = false;

    if (is_numeric($id_or_email)) {
        $user = get_user_by('id', $id_or_email);
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user = get_user_by('id', $id_or_email->user_id);
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
    }

    if (!$user) {
        return $avatar;
    }

    // avatar paths
    $default_avatar = plugin_dir_url(__FILE__) . 'assets/img/user_avatar-240x240.png';
    $current_avatar = plugin_dir_url(__FILE__) . 'assets/img/current_user_avatar-240x240.png';
    $admin_avatar   = plugin_dir_url(__FILE__) . 'assets/img/admin_1_avatar-240x240.png';
    $develooper_avatar = plugin_dir_url(__FILE__) . 'assets/img/admin_2_avatar-240x240.png';
    $LOOPIS_avatar   = plugin_dir_url(__FILE__) . 'assets/img/loopis_green_avatar-240x240.png';

    $current_user = wp_get_current_user();
    if ($user->ID === $current_user->ID) {
        $url = $current_avatar;
    }elseif ($user->user_nicename === 'loopis') {
        $url = $LOOPIS_avatar;
    }elseif (in_array('administrator', $user->roles)) {
        $url = $develooper_avatar;
    }elseif (in_array('manager', $user->roles)) {
        $url = $admin_avatar;
    } else {
        $url = $default_avatar;
    }

    return "<img src='{$url}' class='avatar avatar-{$size}' width='{$size}' height='{$size}' alt='{$alt}' />";
}
