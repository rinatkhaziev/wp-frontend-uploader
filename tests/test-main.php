<?php
/**
 * Frontend Uploader integration tests.
 */

class Frontend_Uploader_UnitTestCase extends WP_UnitTestCase {
	protected $fu;

	public function set_up() {
		parent::set_up();
		$this->fu = $GLOBALS['frontend_uploader'];
	}

	public function test_plugin_is_loaded() {
		$this->assertInstanceOf( Frontend_Uploader::class, $this->fu );
	}

	public function test_upload_form_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'fu-upload-form' ) );
	}

	public function test_upload_response_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'fu-upload-response' ) );
	}

	public function test_radio_input_renders_the_requested_value() {
		$helper = new Html_Helper();
		$input  = $helper->input( 'radio', 'choice', 'yes', array() );

		$this->assertStringContainsString( 'type="radio"', $input );
		$this->assertStringContainsString( 'value="yes"', $input );
	}
}
