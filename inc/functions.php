<?php
/**
 * Plugin functions.
 *
 * @package WP_PHP_Translation_Files
 */

/**
 * Outputs or returns a parsable string representation of a variable.
 *
 * Like {@see var_export()} but "minified", using short array syntax
 * and no newlines.
 *
 * @since 0.0.1
 *
 * @param mixed $value       The variable you want to export.
 * @param bool  $return_only Optional. Whether to return the variable representation instead of outputing it. Default false.
 * @return string|void The variable representation or void.
 */
function wp_php_tf_var_export( $value, $return_only = false ) {
	if ( is_array( $value ) ) {
		$entries = array();
		foreach ( $value as $key => $val ) {
			$entries[] = var_export( $key, true ) . '=>' . wp_php_tf_var_export( $val, true );
		}

		$code = '[' . implode( ',', $entries ) . ']';
		if ( $return_only ) {
			return $code;
		}

		echo $code;
	} else {
		return var_export( $value, $return_only );
	}
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

	$php_mo = new wp_php_tf_PHP_MO();
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
function wp_php_tf_override_load_textdomain2( $override, $domain, $mofile ) {
	global $l10n, $l10n_unloaded, $wp_textdomain_registry;

	$current_locale = determine_locale();

	if ( is_readable( $mofile ) ) {
		$php_mo = str_replace( '.mo', '.php', $mofile );

		if ( ! file_exists( $php_mo ) ) {
			wp_php_tf_create_php_file_from_mo_file( $mofile );
		}

		if ( file_exists( $php_mo ) ) {
			$mo     = new wp_php_tf_PHP_MO();
			$result = $mo->import_from_file( $php_mo );

			// This part here basically does the same as `load_textdomain()`
			// by merging existing translations and updating the registry.
			if ( ! $result ) {
				$wp_textdomain_registry->set( $domain, $current_locale, false );
			} else {
				if ( isset( $l10n[ $domain ] ) ) {
					$mo->merge_with( $l10n[ $domain ] );
				}

				unset( $l10n_unloaded[ $domain ] );

				$l10n[ $domain ] = $mo;

				$wp_textdomain_registry->set( $domain, $current_locale, dirname( $mofile ) );

				return true;
			}
		}
	}

	return $override;
}
