<?php

class Translation_Cleanup_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		add_filter( 'filesystem_method_file', array( $this, 'filter_abstraction_file' ) );
		add_filter( 'filesystem_method', array( $this, 'filter_fs_method' ) );
	}

	public function tear_down() {
		global $wp_filesystem;
		remove_filter( 'filesystem_method_file', array( $this, 'filter_abstraction_file' ) );
		remove_filter( 'filesystem_method', array( $this, 'filter_fs_method' ) );
		unset( $wp_filesystem );

		parent::tear_down();
	}

	public function filter_fs_method( $method ) {
		return 'Dummy';
	}

	public function filter_abstraction_file( $file ) {
		return DIR_PLUGIN_TESTDATA . '/class-wp-filesystem-dummy.php';
	}

	/**
	 * @covers ::wp_php_tf_deleted_plugin
	 */
	public function test_delete_translations_after_plugin_deletion_failed() {
		/**
		 * @var WP_Filesystem_Dummy $wp_filesystem
		 */
		global $wp_filesystem;

		add_filter( 'request_filesystem_credentials', '__return_true' );
		delete_plugins( array( 'non-existent-plugin/non-existent-plugin.php' ) );

		$this->assertNotContains(
			WP_LANG_DIR . '/plugins/internationalized-plugin-de_DE.php',
			$wp_filesystem->get_deleted_files()
		);
	}

	/**
	 * @covers ::wp_php_tf_deleted_plugin
	 */
	public function test_delete_translations_after_plugin_deletion_success() {
		/**
		 * @var WP_Filesystem_Dummy $wp_filesystem
		 */
		global $wp_filesystem;

		add_filter( 'request_filesystem_credentials', '__return_true' );
		delete_plugins( array( 'internationalized-plugin/internationalized-plugin.php' ) );

		$this->assertContains(
			WP_LANG_DIR . '/plugins/internationalized-plugin-de_DE.php',
			$wp_filesystem->get_deleted_files()
		);

		$this->assertContains(
			WP_LANG_DIR . '/plugins/internationalized-plugin-es_ES.php',
			$wp_filesystem->get_deleted_files()
		);
	}

	/**
	 * @covers ::wp_php_tf_deleted_theme
	 */
	public function test_delete_translations_after_theme_deletion_failed() {
		/**
		 * @var WP_Filesystem_Dummy $wp_filesystem
		 */
		global $wp_filesystem;

		add_filter( 'request_filesystem_credentials', '__return_true' );
		delete_theme( 'non-existent-theme' );

		$this->assertNotContains(
			WP_LANG_DIR . '/themes/non-existent-theme-de_DE.php',
			$wp_filesystem->get_deleted_files()
		);
	}

	/**
	 * @covers ::wp_php_tf_deleted_theme
	 */
	public function test_delete_translations_after_theme_deletion_success() {
		/**
		 * @var WP_Filesystem_Dummy $wp_filesystem
		 */
		global $wp_filesystem;

		add_filter( 'request_filesystem_credentials', '__return_true' );
		delete_theme( 'internationalized-theme' );

		$this->assertContains(
			WP_LANG_DIR . '/themes/internationalized-theme-de_DE.php',
			$wp_filesystem->get_deleted_files()
		);
	}
}
