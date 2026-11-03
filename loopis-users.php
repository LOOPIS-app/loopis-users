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
include_once LOOPIS_USERS_DIR . '/includes/filters/loopis-ping.php';
