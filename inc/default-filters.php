<?php
/**
 * Adds all plugin actions and filters.
 *
 * @package WP_PHP_Translation_Files
 */

add_action( 'upgrader_process_complete', 'wp_php_tf_upgrader_process_complete', 10, 2 );
add_filter( 'override_load_textdomain', 'wp_php_tf_override_load_textdomain2', 10, 3 );
