<?php

/**
 * @coversDefaultClass WP_PHP_TF_PHP_MO
 */
class WP_PHP_TF_PHP_MO_Test extends WP_UnitTestCase {
	/**
	 * @covers ::import_from_file
	 */
	public function test_import_from_file_non_existent_file() {
		$mo      = new WP_PHP_TF_PHP_MO();
		$success = $mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/doesnotexist.php' );
		$this->assertFalse( $success );
	}

	/**
	 * @covers ::import_from_file
	 */
	public function test_import_from_file_no_array() {
		$mo      = new WP_PHP_TF_PHP_MO();
		$success = $mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/no_array.php' );
		$this->assertFalse( $success );
	}

	/**
	 * @covers ::import_from_file
	 */
	public function test_import_from_file_invalid_array() {
		$mo      = new WP_PHP_TF_PHP_MO();
		$success = $mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/invalid.php' );
		$this->assertFalse( $success );
	}

	/**
	 * @covers ::import_from_file
	 * @covers ::get_filename
	 */
	public function test_simple() {
		$mo      = new WP_PHP_TF_PHP_MO();
		$success = $mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/simple.php' );
		$this->assertTrue( $success );
		$this->assertSame( DIR_PLUGIN_TESTDATA . '/pomo/simple.php', $mo->get_filename() );
		$this->assertCount( 2, $mo->entries );
		$this->assertSame( array( 'dyado' ), $mo->entries['baba']->translations );
		$this->assertSame( array( 'yes' ), $mo->entries["kuku\nruku"]->translations );
	}

	/**
	 * @covers ::import_from_file
	 * @covers ::make_entry
	 */
	public function test_plural() {
		$mo = new WP_PHP_TF_PHP_MO();
		$mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/plural.php' );
		$this->assertCount( 1, $mo->entries );
		$this->assertSame( array( 'oney dragoney', 'twoey dragoney', 'manyey dragoney', 'manyeyey dragoney', 'manyeyeyey dragoney' ), $mo->entries['one dragon']->translations );

		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 1 ) );
		$this->assertSame( 'twoey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 2 ) );
		$this->assertSame( 'twoey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', -8 ) );

		$mo->set_header( 'Plural-Forms', 'nplurals=5; plural=0' );
		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 1 ) );
		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 2 ) );
		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', -8 ) );

		$mo->set_header( 'Plural-Forms', 'nplurals=5; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;' );
		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 1 ) );
		$this->assertSame( 'manyey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 11 ) );
		$this->assertSame( 'twoey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 3 ) );

		$mo->set_header( 'Plural-Forms', 'nplurals=2; plural=n !=1;' );
		$this->assertSame( 'oney dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 1 ) );
		$this->assertSame( 'twoey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', 2 ) );
		$this->assertSame( 'twoey dragoney', $mo->translate_plural( 'one dragon', '%d dragons', -8 ) );
	}

	/**
	 * @covers ::import_from_file
	 * @covers ::make_entry
	 */
	public function test_context() {
		$mo = new WP_PHP_TF_PHP_MO();
		$mo->import_from_file( DIR_PLUGIN_TESTDATA . '/pomo/context.php' );
		$this->assertCount( 2, $mo->entries );
		$plural_entry = new Translation_Entry(
			array(
				'singular'     => 'one dragon',
				'plural'       => null, // '%d dragons'
				'translations' => array( 'oney dragoney', 'twoey dragoney', 'manyey dragoney' ),
				'context'      => 'dragonland',
				'is_plural'    => true,
			)
		);
		$this->assertEquals( $plural_entry, $mo->entries[ $plural_entry->key() ] );
		$this->assertSame( 'dragonland', $mo->entries[ $plural_entry->key() ]->context );

		$single_entry = new Translation_Entry(
			array(
				'singular'     => 'one dragon',
				'translations' => array( 'oney dragoney' ),
				'context'      => 'not so dragon',
			)
		);
		$this->assertEquals( $single_entry, $mo->entries[ $single_entry->key() ] );
		$this->assertSame( 'not so dragon', $mo->entries[ $single_entry->key() ]->context );
	}

	/**
	 * @covers ::export_to_file
	 */
	public function test_export_php_file() {
		$entries              = array();
		$entries[]            = new Translation_Entry(
			array(
				'singular'     => 'pink',
				'translations' => array( 'розов' ),
			)
		);
		$no_translation_entry = new Translation_Entry( array( 'singular' => 'grey' ) );
		$entries[]            = new Translation_Entry(
			array(
				'singular'     => 'green',
				'plural'       => null,
				'translations' => array( 'зелен', 'зелени' ),
				'is_plural'    => true,
			)
		);
		$entries[]            = new Translation_Entry(
			array(
				'singular'     => 'red',
				'context'      => 'color',
				'translations' => array( 'червен' ),
			)
		);
		$entries[]            = new Translation_Entry(
			array(
				'singular'     => 'red',
				'context'      => 'bull',
				'translations' => array( 'бик' ),
			)
		);
		$entries[]            = new Translation_Entry(
			array(
				'singular'     => 'maroon',
				'plural'       => null,
				'context'      => 'context',
				'translations' => array( 'пурпурен', 'пурпурни' ),
				'is_plural'    => true,
			)
		);

		$mo = new WP_PHP_TF_PHP_MO();
		$mo->set_header( 'Language', 'ru_RU' );
		$mo->set_header( 'Project-Id-Version', 'Baba Project 1.0' );
		$mo->set_header( 'Plural-Forms', 'nplurals=5; plural=0' );
		foreach ( $entries as $entry ) {
			$mo->add_entry( $entry );
		}
		$mo->add_entry( $no_translation_entry );

		$temp_fn = $this->temp_filename();
		$mo->export_to_file( $temp_fn );

		$again = new WP_PHP_TF_PHP_MO();
		$again->import_from_file( $temp_fn );

		$this->assertCount( count( $entries ), $again->entries );
		foreach ( $entries as $entry ) {
			$this->assertEquals( $entry, $again->entries[ $entry->key() ] );
		}
	}

	/**
	 * @covers ::export_to_file
	 */
	public function test_export_should_not_include_empty_translations() {
		$mo = new WP_PHP_TF_PHP_MO();
		$mo->add_entry(
			array(
				'singular'     => 'baba',
				'translations' => array( '', '' ),
			)
		);

		$temp_fn = $this->temp_filename();
		$mo->export_to_file( $temp_fn );

		$again = new WP_PHP_TF_PHP_MO();
		$again->import_from_file( $temp_fn );

		$this->assertCount( 0, $again->entries );
	}
}
