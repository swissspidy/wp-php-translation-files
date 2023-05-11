<?php

class Plugin_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();

		// Allows removing newly added language files but keeping the ones
		// already provided by the test suite.
		self::$ignore_files = array_merge( self::$ignore_files, $this->files_in_dir( WP_LANG_DIR ) );

		$this->rmdir( WP_LANG_DIR );

		$GLOBALS['l10n']          = array();
		$GLOBALS['l10n_unloaded'] = array();
	}

	public function tear_down() {
		$this->rmdir( WP_LANG_DIR );

		parent::tear_down();
	}

	/**
	 * @covers ::wp_php_tf_create_php_file_from_mo_file
	 */
	public function test_wp_php_tf_create_php_file_from_mo_file() {
		$mo_file  = WP_PLUGIN_DIR . '/custom-internationalized-plugin/languages/custom-internationalized-plugin-de_DE.mo';
		$php_file = WP_PLUGIN_DIR . '/custom-internationalized-plugin/languages/custom-internationalized-plugin-de_DE.php';
		wp_php_tf_create_php_file_from_mo_file( $mo_file );

		$this->assertTrue( file_exists( $php_file ) );

		$contents = file_get_contents( $php_file );

		$expected_contents = <<<CONTENTS
<?php
return ['translation-revision-date'=>'+0000','generator'=>'WordPress/VERSION','messages'=>['This is a dummy plugin'=>['Das ist ein Dummy Plugin']]];

CONTENTS;

		$expected_contents = str_replace( 'WordPress/VERSION', 'WordPress/' . get_bloginfo( 'version' ), $expected_contents );

		$this->unlink( $php_file ); // Just for cleanup.

		$this->assertSame( $expected_contents, $contents );
	}

	/**
	 * @covers ::wp_php_tf_upgrader_process_complete
	 */
	public function test_create_translation_files_after_translations_update() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-language-pack-upgrader.php';
		require_once DIR_PLUGIN_TESTDATA . '/class-dummy-upgrader-skin.php';
		require_once DIR_PLUGIN_TESTDATA . '/class-dummy-language-pack-upgrader.php';

		$upgrader = new Dummy_Language_Pack_Upgrader( new Dummy_Upgrader_Skin() );

		// These translations exist in the core test suite.
		// See https://github.com/WordPress/wordpress-develop/tree/e3d345800d3403f3902dc7b18c1ddb07158b0bd3/tests/phpunit/data/languages.
		$result = $upgrader->bulk_upgrade(
			array(
				(object) array(
					'type'     => 'plugin',
					'slug'     => 'internationalized-plugin',
					'language' => 'de_DE',
					'version'  => '99.9.9',
					'package'  => '/tmp/notused.zip',
				),
				(object) array(
					'type'     => 'theme',
					'slug'     => 'internationalized-theme',
					'language' => 'de_DE',
					'version'  => '99.9.9',
					'package'  => '/tmp/notused.zip',
				),
				(object) array(
					'type'     => 'core',
					'slug'     => 'default',
					'language' => 'de_DE',
					'version'  => '99.9.9',
					'package'  => '/tmp/notused.zip',
				),
			)
		);

		$plugin = WP_LANG_DIR . '/plugins/internationalized-plugin-de_DE.php';
		$theme  = WP_LANG_DIR . '/themes/internationalized-theme-de_DE.php';
		$core   = WP_LANG_DIR . '/de_DE.php';

		$plugin_exists = file_exists( $plugin );
		$theme_exists  = file_exists( $theme );
		$core_exists   = file_exists( $core );

		unlink( $plugin );
		unlink( $theme );
		unlink( $core );

		$this->assertIsNotBool( $result );
		$this->assertNotWPError( $result );
		$this->assertNotEmpty( $result );

		$this->assertTrue( $plugin_exists );
		$this->assertTrue( $theme_exists );
		$this->assertTrue( $core_exists );
	}

	/**
	 * @covers ::wp_php_tf_upgrader_process_complete
	 */
	public function test_do_not_create_translations_after_plugin_update() {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		require_once DIR_PLUGIN_TESTDATA . '/class-dummy-upgrader-skin.php';
		require_once DIR_PLUGIN_TESTDATA . '/class-dummy-plugin-upgrader.php';

		$upgrader = new Dummy_Plugin_Upgrader( new Dummy_Upgrader_Skin() );

		set_site_transient(
			'update_plugins',
			(object) array(
				'response' => array(
					'custom-internationalized-plugin/custom-internationalized-plugin.php' => (object) array(
						'package' => 'https://urltozipfile.local',
					),
				),
			)
		);

		$result = $upgrader->bulk_upgrade(
			array(
				'custom-internationalized-plugin/custom-internationalized-plugin.php',
			)
		);

		$this->assertNotFalse( $result );
		$this->assertFalse( file_exists( WP_LANG_DIR . '/plugins/custom-internationalized-plugin-de_DE.php' ) );
		$this->assertFalse( file_exists( WP_PLUGIN_DIR . '/plugins/custom-internationalized-plugin/custom-internationalized-plugin-de_DE.php' ) );
	}

	/**
	 * @covers ::wp_php_tf_override_load_textdomain
	 */
	public function test_override_load_textdomain_invalid_file() {
		$this->assertFalse( load_textdomain( 'internationalized-plugin', WP_LANG_DIR . '/plugins/non-existent-plugin-de_DE.mo' ) );
	}

	/**
	 * @covers ::wp_php_tf_override_load_textdomain
	 */
	public function test_override_load_textdomain_success() {
		$plugin = WP_LANG_DIR . '/plugins/internationalized-plugin-de_DE.php';

		$result = load_textdomain( 'internationalized-plugin', WP_LANG_DIR . '/plugins/internationalized-plugin-de_DE.mo' );

		$file_exists = file_exists( $plugin );

		unlink( $plugin );

		$this->assertTrue( $result );
		$this->assertTrue( $file_exists );
	}
}
