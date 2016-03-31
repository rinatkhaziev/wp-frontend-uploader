<?php
/**
 * fu test suite
 */

// Composer autoload
class Frontend_Uploader_UnitTestCase extends WP_UnitTestCase {
	public $fu;

	/**
	 * Init
	 *
	 * @return [type] [description]
	 */
	function setUp() {
		$this->fu = $GLOBALS['frontend_uploader'];
	}

	function tearDown() {
	}

	function test_dummy() {
		$this->assertFalse( false );
	}

}
