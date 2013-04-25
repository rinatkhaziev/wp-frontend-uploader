<?php
/**
 *
 * Test case for Frontend Uploader
 *
 */
class Frontend_Uploader_UnitTestCase extends WP_UnitTestCase {
	public $fu;

	/**
	 * Init
	 * @return [type] [description]
	 */
	function setup() {
		$this->fu = new Frontend_Uploader;
		parent::setup();
	}

	function teardown() {
	}

	// Check if settings get set up on activation
	function test_default_settings() {);
		$this->assertNotEmpty( $this->fu->settings );
	}

	// Test if the post has gallery shortcode and needs to be updated with the new att id
	function test_gallery_shortcode_update() {

	}

	// Check if errors are handled properly
	function test_error_handling() {

	}
}