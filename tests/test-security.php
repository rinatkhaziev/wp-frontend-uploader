<?php
/**
 * Upload and moderation security tests.
 */

class Frontend_Uploader_Security_Test extends Frontend_Uploader_Test_Case {
	/**
	 * @dataProvider suspicious_payload_provider
	 */
	public function test_suspicious_file_payloads_are_detected( $payload ) {
		$this->assertGreaterThan( 0, $this->fu->_invoke_paranoia_on_file_contents( $payload ) );
	}

	public function suspicious_payload_provider() {
		return array(
			'php opening tag' => array( '<?php echo "unsafe";' ),
			'eval call'       => array( 'eval ( $payload );' ),
			'base64 decode'   => array( 'base64_decode( $payload )' ),
			'gzinflate call'  => array( 'gzinflate( $payload )' ),
			'gzuncompress'    => array( 'gzuncompress( $payload )' ),
		);
	}

	public function test_benign_file_payload_is_not_flagged() {
		$this->assertSame( 0, $this->fu->_invoke_paranoia_on_file_contents( 'A normal image caption.' ) );
	}

	public function test_non_string_file_payload_is_not_flagged() {
		$this->assertSame( 0, $this->fu->_invoke_paranoia_on_file_contents( array( 'not', 'a', 'file' ) ) );
	}

	public function test_executable_extensions_are_removed_from_allowed_mime_types() {
		$core_mimes = get_allowed_mime_types();
		$jpeg_key   = array_search( 'image/jpeg', $core_mimes, true );
		$warnings   = array();

		$this->assertNotFalse( $jpeg_key );

		$this->fu->settings['enabled_files'] = array(
			$jpeg_key => 'image/jpeg',
			'php'     => 'text/x-php',
			'svg'     => 'image/svg+xml',
		);

		set_error_handler(
			function ( $severity, $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			}
		);

		try {
			$allowed = $this->fu->_get_mime_types();
		} finally {
			restore_error_handler();
		}

		$this->assertSame( 'image/jpeg', $allowed[ $jpeg_key ] );
		$this->assertArrayNotHasKey( 'php', $allowed );
		$this->assertArrayNotHasKey( 'svg', $allowed );
		$this->assertCount( 1, $warnings );
		$this->assertStringContainsString( 'blocks uploads of executable file types', implode( ' ', $warnings ) );
	}

	public function test_moderation_nonce_is_scoped_to_action_and_post() {
		$post_id = self::factory()->post->create();
		$action  = $this->fu->get_moderation_nonce_action( 'approve', $post_id );
		$nonce   = wp_create_nonce( $action );

		$this->assertNotFalse( wp_verify_nonce( $nonce, $action ) );
		$this->assertFalse( wp_verify_nonce( $nonce, $this->fu->get_moderation_nonce_action( 'delete', $post_id ) ) );
		$this->assertFalse( wp_verify_nonce( $nonce, $this->fu->get_moderation_nonce_action( 'approve', $post_id + 1 ) ) );
	}

	public function test_only_configured_registered_post_types_are_allowed() {
		register_post_type( 'fu_book', array( 'public' => true ) );
		try {
			$this->fu->settings['enabled_post_types'] = array(
				'post'    => 'Posts',
				'fu_book' => 'Books',
				'missing' => 'Missing',
			);

			$this->assertTrue( $this->fu->is_allowed_post_type( 'post' ) );
			$this->assertTrue( $this->fu->is_allowed_post_type( 'fu_book' ) );
			$this->assertFalse( $this->fu->is_allowed_post_type( 'page' ) );
			$this->assertFalse( $this->fu->is_allowed_post_type( 'missing' ) );
		} finally {
			unregister_post_type( 'fu_book' );
		}
	}
}
