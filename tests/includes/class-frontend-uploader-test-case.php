<?php
/**
 * Shared state management for Frontend Uploader integration tests.
 */

abstract class Frontend_Uploader_Test_Case extends WP_UnitTestCase {
	protected $fu;

	private $original_files;
	private $original_get;
	private $original_post;
	private $original_request;
	private $original_shortcodes;
	private $original_settings;
	private $original_allowed_mime_types;
	private $original_form_fields;
	private $original_global_post;
	private $had_original_global_post;
	private $original_is_debug;
	private $original_user_id;

	public function set_up() {
		parent::set_up();

		$this->fu                          = $GLOBALS['frontend_uploader'];
		$this->original_files              = $_FILES;
		$this->original_get                = $_GET;
		$this->original_post               = $_POST;
		$this->original_request            = $_REQUEST;
		$this->original_shortcodes          = $GLOBALS['shortcode_tags'];
		$this->original_settings           = $this->fu->settings;
		$this->original_allowed_mime_types = $this->fu->allowed_mime_types;
		$this->original_form_fields        = $this->fu->form_fields;
		$this->had_original_global_post    = array_key_exists( 'post', $GLOBALS );
		$this->original_global_post        = $this->had_original_global_post ? $GLOBALS['post'] : null;
		$this->original_is_debug           = $this->fu->is_debug;
		$this->original_user_id            = get_current_user_id();

		$_FILES           = array();
		$_GET             = array();
		$_POST            = array();
		$_REQUEST         = array();
		$this->fu->form_fields = array();
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		$_FILES                     = $this->original_files;
		$_GET                       = $this->original_get;
		$_POST                      = $this->original_post;
		$_REQUEST                   = $this->original_request;
		$GLOBALS['shortcode_tags']  = $this->original_shortcodes;
		$this->fu->settings         = $this->original_settings;
		$this->fu->allowed_mime_types = $this->original_allowed_mime_types;
		$this->fu->form_fields      = $this->original_form_fields;
		$this->fu->is_debug         = $this->original_is_debug;
		if ( $this->had_original_global_post ) {
			$GLOBALS['post'] = $this->original_global_post;
		} else {
			unset( $GLOBALS['post'] );
		}
		wp_set_current_user( $this->original_user_id );

		parent::tear_down();
	}

	protected function capture_output( $callback ) {
		ob_start();
		try {
			call_user_func( $callback );
			return ob_get_clean();
		} catch ( Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}
}
