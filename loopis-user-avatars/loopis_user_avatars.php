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

    // Local image paths
    $user_default_url = plugin_dir_url(__FILE__) . 'assets/img/avatar/user_default.png';
    $user_manager_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar/user_manager.png';
    $user_current_url = plugin_dir_url(__FILE__) . 'assets/img/avatar/user_current.png';
    $user_manager_current_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar/user_manager_current.png';
    $loopis_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar/loopis.png';
    $lotten_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar/lotten.png';
    $nisse_url   = plugin_dir_url(__FILE__) . 'assets/img/avatar/nisse.png';

    $current_user = wp_get_current_user();

    if ($user->user_nicename === 'loopis') {
        $url = $loopis_url;
    } elseif ($user->user_nicename === 'lotten') {
        $url = $lotten_url;
    } elseif ($user->user_nicename === 'nisse') {
        $url = $nisse_url;
    } elseif ($user->ID === $current_user->ID) {
        if (in_array('manager', $user->roles)) {
            $url = $user_manager_current_url;
        }else{
            $url = $user_current_url;
        }
    } elseif (in_array('manager', $user->roles)) {
        $url = $user_manager_url;
    } else {
        $url = $user_default_url;
    }

    return "<img src='{$url}' class='avatar avatar-{$size}' width='{$size}' height='{$size}' alt='{$alt}' />";
}
