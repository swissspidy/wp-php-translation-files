<?php

/**
 * Dummy skin for the WordPress Upgrader classes during tests.
 *
 * @see WP_Upgrader
 */
class Dummy_Upgrader_Skin extends WP_Upgrader_Skin {
	/**
	 * @return void
	 */
	public function header() {}

	/**
	 * @return void
	 */
	public function footer() {}
}
