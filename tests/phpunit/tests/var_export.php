<?php

class Var_Export_Test extends WP_UnitTestCase {
	/**
	 * @covers ::wp_php_tf_var_export
	 * @dataProvider data_test_wp_php_tf_var_export
	 */
	public function test_wp_php_tf_var_export( $input, $expected ) {
		$this->assertSame( $expected, wp_php_tf_var_export( $input, true ) );
		$this->assertSame(
			$expected,
			get_echo(
				function() use ( $input ) {
					wp_php_tf_var_export( $input );
				}
			)
		);
	}

	public function data_test_wp_php_tf_var_export() {
		return array(
			'Integer'       => array(
				1234,
				'1234',
			),
			'Float'         => array(
				12.34,
				'12.34',
			),
			'Boolean'       => array(
				true,
				'true',
			),
			'Complex Array' => array(
				array(
					'Foo'    => array(
						'Bar' => 'Baz',
					),
					'Foobar' => array(
						'Lorem' => array(
							'ipsum' => 'dolor',
						),
					),
					'Barbaz' => 1234,
				),
				"['Foo'=>['Bar'=>'Baz'],'Foobar'=>['Lorem'=>['ipsum'=>'dolor']],'Barbaz'=>1234]",
			),
			'List Array'    => array(
				array(
					'Foo'    => array(
						'Bar',
						'Baz',
					),
					'Foobar' => array(
						'Lorem',
						'ipsum',
						'dolor',
					),
					'Barbaz' => 1234,
				),
				"['Foo'=>['Bar','Baz'],'Foobar'=>['Lorem','ipsum','dolor'],'Barbaz'=>1234]",
			),
		);
	}
}
