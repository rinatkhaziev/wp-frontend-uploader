<?php
/**
 * fu test suite
 */

// Composer autoload
class fu_UnitTestCase extends WP_UnitTestCase {
	public $fu;

	/**
	 * Init
	 *
	 * @return [type] [description]
	 */
	function setup() {
		parent::setup();
		$this->fu = $GLOBALS['frontend-uploader'];

	}

	function teardown() {
	}

	function test_dummy() {
		$this->assertEquals( 1, 1 );
	}

}
