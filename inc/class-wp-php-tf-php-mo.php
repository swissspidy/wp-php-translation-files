<?php
/**
 * Class for working with PHP "MO" files
 *
 * @since 0.0.1
 *
 * @package WP_PHP_Translation_Files
 */

/**
 * PHP MO class.
 *
 * @since 0.0.1
 */
class WP_PHP_TF_PHP_MO extends Gettext_Translations {

	/**
	 * Number of plural forms.
	 *
	 * @var int
	 */
	public $_nplurals = 2;

	/**
	 * Loaded MO file.
	 *
	 * @var string
	 */
	private $filename = '';

	/**
	 * Returns the loaded MO file.
	 *
	 * @return string The loaded MO file.
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * Fills up with the entries from MO file $filename.
	 *
	 * @param string $filename MO file to load.
	 * @return bool True if the import from file was successful, otherwise false.
	 */
	public function import_from_file( $filename ) {
		if ( ! file_exists( $filename ) ) {
			return false;
		}

		$this->filename = (string) $filename;

		$translations = include $filename;

		if ( ! is_array( $translations ) || ! isset( $translations['messages'] ) ) {
			return false;
		}

		$headers = array(
			'plural-forms' => 'Plural-Forms',
			'generator'    => 'X-Generator',
			'domain'       => 'X-Domain',
			'language'     => 'Language',
		);

		foreach ( $headers as $php_header => $po_header ) {
			if ( ! empty( $translations[ $php_header ] ) ) {
				$this->set_header( $po_header, $translations[ $php_header ] );
			}
		}

		foreach ( $translations['messages'] as $original => $translation ) {
			$entry                          = &$this->make_entry( $original, implode( "\0", $translation ) );
			$entry->is_plural               = count( $translation ) > 1;
			$this->entries[ $entry->key() ] = &$entry;
		}

		return true;
	}

	/**
	 * Export entries to file.
	 *
	 * @param string $filename File name.
	 * @return bool True on success, false on failure.
	 */
	public function export_to_file( $filename ) {
		$fh = fopen( $filename, 'wb' );
		if ( ! $fh ) {
			return false;
		}

		$po_file_data = array(
			'translation-revision-date' => '+0000',
			'generator'                 => 'WordPress/' . get_bloginfo( 'version' ),
			'messages'                  => array(),
		);

		/**
		 * Translation entry.
		 *
		 * @var Translation_Entry $entry
		 */
		foreach ( $this->entries as $key => $entry ) {
			if ( empty( array_filter( $entry->translations ) ) ) {
				continue;
			}
			$po_file_data['messages'][ $key ] = $entry->translations;
		}

		$language = $this->get_header( 'Language' );

		if ( $language ) {
			$po_file_data['language'] = $language;
		}

		$plural_form = $this->get_header( 'Plural-Forms' );

		if ( $plural_form ) {
			$po_file_data['plural-forms'] = $plural_form;
		}

		$export = '<?php' . PHP_EOL . 'return ' . wp_php_tf_var_export( $po_file_data, true ) . ';' . PHP_EOL;

		$res = fwrite( $fh, $export );

		fclose( $fh );

		return $res;
	}

	/**
	 * Build a Translation_Entry from original string and translation strings,
	 * found in a MO file
	 *
	 * @static
	 * @param string $original    Original string to translate from MO file. Might contain
	 *                            0x04 as context separator or 0x00 as singular/plural separator.
	 * @param string $translation translation string from MO file. Might contain
	 *                            0x00 as a plural translations separator.
	 * @return Translation_Entry Entry instance.
	 */
	public function &make_entry( $original, $translation ) {
		$entry = new Translation_Entry();
		// Look for context, separated by \4.
		$parts = explode( "\4", $original );
		if ( isset( $parts[1] ) ) {
			$original       = $parts[1];
			$entry->context = $parts[0];
		}

		$entry->singular = $original;

		$translations = explode( "\0", $translation );

		if ( count( $translations ) > 1 ) {
			$entry->is_plural = true;
		}

		$entry->translations = $translations;

		return $entry;
	}

	/**
	 * Get the plural form for a given count.
	 *
	 * @param int $count Count.
	 * @return string Plural form.
	 */
	public function select_plural_form( $count ) {
		return $this->gettext_select_plural_form( $count );
	}

	/**
	 * Returns the plural forms count.
	 *
	 * @return int Plural forms count.
	 */
	public function get_plural_forms_count() {
		return $this->_nplurals;
	}
}
