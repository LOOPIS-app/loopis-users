<?php

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

add_filter('get_avatar', 'loopis_avatars', 10, 5);

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
    $admin_avatar   = plugin_dir_url(__FILE__) . 'assets/img/admin_1_avatar-240x240.png';
    $develooper_avatar = plugin_dir_url(__FILE__) . 'assets/img/admin_2_avatar-240x240.png';
    $LOOPIS_avatar   = plugin_dir_url(__FILE__) . 'assets/img/LOOP_avatar-240x240.png';

    // check if admin
    if ($user->user_nicename === 'loopis') {
        $url = $LOOPIS_avatar;
    }elseif (in_array('administrator', $user->roles)) {
        $url = $admin_avatar;
    } else {
        $url = $default_avatar;
    }

    return "<img src='{$url}' class='avatar avatar-{$size}' width='{$size}' height='{$size}' alt='{$alt}' />";
}