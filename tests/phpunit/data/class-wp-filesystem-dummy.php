<?php

require DIR_TESTROOT . '/includes/mock-fs.php';

/**
 * Dummy skin for the WordPress Upgrader classes during tests.
 *
 * @see WP_Upgrader
 */
class WP_Filesystem_Dummy extends WP_Filesystem_MockFS {
	private $deletions = array();

	public function connect() {
		$this->init( '/' );
		$this->mkdir( WP_PLUGIN_DIR );
		$this->mkdir( trailingslashit( WP_PLUGIN_DIR ) . 'internationalized-plugin' );
		$this->mkdir( get_theme_root( 'internationalized-theme' ) );
		$this->mkdir( get_theme_root( 'internationalized-theme' ) . '/internationalized-theme' );

		return true;
	}

	/**
	 * Deletes a file or directory.
	 *
	 * @param string       $file      Path to the file or directory.
	 * @param bool         $recursive Optional. If set to true, deletes files and folders recursively.
	 *                                Default false.
	 * @param string|false $type      Type of resource. 'f' for file, 'd' for directory.
	 *                                Default false.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $file, $recursive = false, $type = false ) {
		$this->deletions[] = $file;
		return $this->is_dir( $file ) || $this->is_file( $file );
	}

	/**
	 * Returns the path on the remote filesystem of the Themes Directory.
	 *
	 * @since 2.7.0
	 *
	 * @param string|false $theme Optional. The theme stylesheet or template for the directory.
	 *                            Default false.
	 * @return string The location of the remote path.
	 */
	public function wp_themes_dir( $theme = false ) {
		return parent::wp_themes_dir( 'internationalized-theme' );
	}

	public function get_deleted_files() {
		return $this->deletions;
	}
}
