<?php
/**
 * Plugin Name: WP PHP Translation Files
 * Plugin URI:  https://github.com/swissspidy/wp-php-translation-files
 * Description: Use PHP files for translations instead of MO files.
 * Version:     0.0.1
 * Author:      Pascal Birchler
 * Author URI:  https://pascalbirchler.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-php-translation-files
 * Requires at least: 6.2
 * Requires PHP: 5.6
 *
 * Copyright (c) 2023 Pascal Birchler (email: swissspidy@chat.wordpress.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package WP_PHP_Translation_Files
 */

/**
 * Plugin functions.
 */
require_once __DIR__ . '/inc/functions.php';

/**
 * PHP_MO class.
 */
require_once __DIR__ . '/inc/class-wp-php-tf-php-mo.php';

/**
 * Adds all plugin actions and filters.
 */
require_once __DIR__ . '/inc/default-filters.php';
