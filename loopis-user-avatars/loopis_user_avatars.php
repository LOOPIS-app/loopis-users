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
    $default_url = plugin_dir_url(__FILE__) . 'assets/img/avatar_user_default.png';
    $current_url = plugin_dir_url(__FILE__) . 'assets/img/avatar_user_current.png';
    $manager_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar_user_manager.png';
    $loopis_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar_loopis.png';
    $lotten_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar_lotten.png';
    $nisse_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar_nisse.png';

    $current_user = wp_get_current_user();
    if ($user->ID === $current_user->ID) {
        $url = $current_url;
    } elseif ($user->user_nicename === 'loopis') {
        $url = $loopis_url;
    } elseif ($user->user_nicename === 'lotten') {
        $url = $lotten_url;
    } elseif ($user->user_nicename === 'nisse') {
        $url = $nisse_url;
    } elseif (in_array('manager', $user->roles)) {
        $url = $manager_url;
    } else {
        $url = $default_url;
    }

    return "<img src='{$url}' class='avatar avatar-{$size}' width='{$size}' height='{$size}' alt='{$alt}' />";
}
