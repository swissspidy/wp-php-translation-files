<?php
/**
 * Plugin functions.
 *
 * @package WP_PHP_Translation_Files
 */

/**
 * Determines if the given array is a list.
 *
 * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array)-1.
 *
 * Polyfill for array_is_list() in PHP 8.1.
 *
 * @see https://github.com/symfony/polyfill-php81/tree/main
 *
 * @codeCoverageIgnore
 *
 * @param array $arr The array being evaluated.
 * @return bool True if array is a list, false otherwise.
 */
function wp_php_tf_array_is_list( $arr ) {
	if ( function_exists( 'array_is_list' ) ) {
		return array_is_list( $arr );
	}

	if ( ( array() === $arr ) || ( array_values( $arr ) === $arr ) ) {
		return true;
	}

	$next_key = -1;

	foreach ( $arr as $k => $v ) {
		if ( ++$next_key !== $k ) {
			return false;
		}
	}

	return true;
}

/**
 * Outputs or returns a parsable string representation of a variable.
 *
 * Like {@see var_export()} but "minified", using short array syntax
 * and no newlines.
 *
 * @since 0.0.1
 *
 * @param mixed $value       The variable you want to export.
 * @param bool  $return_only Optional. Whether to return the variable representation instead of outputting it. Default false.
 * @return string|void The variable representation or void.
 */
function wp_php_tf_var_export( $value, $return_only = false ) {
	if ( ! is_array( $value ) ) {
		return var_export( $value, $return_only );
	}

	$entries = array();

	$is_list = wp_php_tf_array_is_list( $value );

	foreach ( $value as $key => $val ) {
		$entries[] = $is_list ? wp_php_tf_var_export( $val, true ) : var_export( $key, true ) . '=>' . wp_php_tf_var_export( $val, true );
	}

	$code = '[' . implode( ',', $entries ) . ']';
	if ( $return_only ) {
		return $code;
	}

	echo $code;
}

/**
 * Creates a PHP translation file for a given MO file.
 *
 * @since 0.0.1
 *
 * @param string $mofile MO file path.
 * @return void
 */
function wp_php_tf_create_php_file_from_mo_file( $mofile ) {
	$mo     = new MO();
	$result = $mo->import_from_file( $mofile );

	if ( ! $result ) {
		return;
	}

	$php_mo = new WP_PHP_TF_PHP_MO();
	$php_mo->merge_with( $mo );

	$php_mo->export_to_file( str_replace( '.mo', '.php', $mofile ) );
}

/**
 * Creates PHP translation files after the translation updates process.
 *
 * @since 0.0.1
 *
 * @param WP_Upgrader $upgrader   WP_Upgrader instance. In other contexts this might be a
 *                                Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
 * @param array       $hook_extra {
 *     Array of bulk item update data.
 *
 *     @type string $action       Type of action. Default 'update'.
 *     @type string $type         Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
 *     @type bool   $bulk         Whether the update process is a bulk update. Default true.
 *     @type array  $plugins      Array of the basename paths of the plugins' main files.
 *     @type array  $themes       The theme slugs.
 *     @type array  $translations {
 *         Array of translations update data.
 *
 *         @type string $language The locale the translation is for.
 *         @type string $type     Type of translation. Accepts 'plugin', 'theme', or 'core'.
 *         @type string $slug     Text domain the translation is for. The slug of a theme/plugin or
 *                                'default' for core translations.
 *         @type string $version  The version of a theme, plugin, or core.
 *     }
 * }
 */
function wp_php_tf_upgrader_process_complete( $upgrader, $hook_extra ) {
	if ( 'translation' !== $hook_extra['type'] || empty( $hook_extra['translations'] ) ) {
		return;
	}

	foreach ( $hook_extra['translations'] as $translation ) {
		switch ( $translation['type'] ) {
			case 'plugin':
				$file = WP_LANG_DIR . '/plugins/' . $translation['slug'] . '-' . $translation['language'] . '.mo';
				break;
			case 'theme':
				$file = WP_LANG_DIR . '/themes/' . $translation['slug'] . '-' . $translation['language'] . '.mo';
				break;
			default:
				$file = WP_LANG_DIR . '/' . $translation['language'] . '.mo';
				break;
		}

		if ( file_exists( $file ) ) {
			wp_php_tf_create_php_file_from_mo_file( $file );
		}
	}
}

/**
 * Filters whether to override the .mo file loading.
 *
 * Used for supporting translation merging.
 *
 * @since 1.7.1
 *
 * @param bool   $override Whether to override the .mo file loading. Default false.
 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
 * @param string $mofile   Path to the MO file.
 * @return bool Whether to override the .mo file loading.
 */
function wp_php_tf_override_load_textdomain( $override, $domain, $mofile ) {
	global $l10n, $l10n_unloaded, $wp_textdomain_registry;

	$current_locale = determine_locale();

	if ( ! is_readable( $mofile ) ) {
		return $override;
	}

	$php_mo = str_replace( '.mo', '.php', $mofile );

	if ( ! file_exists( $php_mo ) ) {
		wp_php_tf_create_php_file_from_mo_file( $mofile );
	}

	if ( ! file_exists( $php_mo ) ) {
		return $override;
	}

	$mo     = new WP_PHP_TF_PHP_MO();
	$result = $mo->import_from_file( $php_mo );

	if ( ! $result ) {
		$wp_textdomain_registry->set( $domain, $current_locale, false );

		return $override;
	}

	// This part here basically does the same as `load_textdomain()`
	// by merging existing translations and updating the registry.
	if ( isset( $l10n[ $domain ] ) ) {
		$mo->merge_with( $l10n[ $domain ] );
	}

	unset( $l10n_unloaded[ $domain ] );

	$l10n[ $domain ] = $mo;

	$wp_textdomain_registry->set( $domain, $current_locale, dirname( $mofile ) );

	return true;
}

/**
 * Deletes translation after successful plugin deletion.
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 * @param bool   $deleted     Whether the plugin deletion was successful.
 */
function wp_php_tf_deleted_plugin( $plugin_file, $deleted ) {
	global $wp_filesystem;

	if ( ! $deleted ) {
		return;
	}

	$plugin_slug = dirname( $plugin_file );

	if ( 'hello.php' === $plugin_file ) {
		$plugin_slug = 'hello-dolly';
	}

	$plugin_translations = wp_get_installed_translations( 'plugins' );

	// Remove language files, silently.
	if ( '.' !== $plugin_slug && ! empty( $plugin_translations[ $plugin_slug ] ) ) {
		$translations = $plugin_translations[ $plugin_slug ];

		foreach ( $translations as $translation => $data ) {
			$wp_filesystem->delete( WP_LANG_DIR . '/plugins/' . $plugin_slug . '-' . $translation . '.php' );
		}
	}
}

/**
 * Deletes translation after successful theme deletion.
 *
 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem subclass.
 *
 * @param string $stylesheet Stylesheet of the theme to delete.
 * @param bool   $deleted    Whether the plugin deletion was successful.
 */
function wp_php_tf_deleted_theme( $stylesheet, $deleted ) {
	global $wp_filesystem;

	if ( ! $deleted ) {
		return;
	}

	$theme_translations = wp_get_installed_translations( 'themes' );

	// Remove language files, silently.
	if ( ! empty( $theme_translations[ $stylesheet ] ) ) {
		$translations = $theme_translations[ $stylesheet ];

		foreach ( $translations as $translation => $data ) {
			$wp_filesystem->delete( WP_LANG_DIR . '/themes/' . $stylesheet . '-' . $translation . '.php' );
		}
	}
}
