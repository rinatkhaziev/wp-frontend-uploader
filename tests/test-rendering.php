<?php
/**
 * Frontend form and response rendering tests.
 */

class Frontend_Uploader_Rendering_Test extends Frontend_Uploader_Test_Case {
	public function test_notice_html_escapes_message_and_class() {
		$notice = $this->fu->_notice_html( '<script>alert(1)</script>', 'success" onclick="alert(1)' );

		$this->assertStringNotContainsString( '<script>', $notice );
		$this->assertStringNotContainsString( 'onclick="alert(1)"', $notice );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $notice );
	}

	public function test_response_notice_renders_known_error_without_untrusted_markup() {
		$output = $this->capture_output(
			function () {
				$this->fu->_display_response_notices(
					array(
						'response' => 'fu-error',
						'errors'   => array(
							'fu-error-media' => array( '<img src=x onerror=alert(1)>' ),
						),
					)
				);
			}
		);

		$this->assertStringContainsString( 'There was an error with your submission', $output );
		$this->assertStringContainsString( 'failure', $output );
		$this->assertStringNotContainsString( '<img', $output );
		$this->assertStringNotContainsString( 'onerror=', $output );
	}

	public function test_post_media_form_contains_scoped_parent_nonce_and_field_map() {
		$page_id         = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$GLOBALS['post'] = get_post( $page_id );
		setup_postdata( $GLOBALS['post'] );

		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array(
				'enable_recaptcha_protection' => 'off',
				'suppress_default_fields'      => 'off',
			)
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout'   => 'post_media',
				'post_id'       => $page_id,
				'append_to_post' => true,
			)
		);

		$this->assertMatchesRegularExpression( '/name="fu_parent_nonce" value="([^"]+)"/', $form );
		preg_match( '/name="fu_parent_nonce" value="([^"]+)"/', $form, $nonce_match );
		$this->assertNotFalse( wp_verify_nonce( $nonce_match[1], FU_PARENT_NONCE . '-' . $page_id ) );
		$this->assertMatchesRegularExpression( '/<input(?=[^>]*name="append_to_post")(?=[^>]*value="1")[^>]*>/', $form );

		preg_match( '/name="ff" value="([a-f0-9]+)"/', $form, $hash_match );
		$this->assertNotEmpty( $hash_match[1] );
		$fields = $this->fu->_get_fields_for_form( $page_id, $hash_match[1] );
		$this->assertIsArray( $fields );
		$this->assertContains( 'post_type', $fields['internal'] );
		$this->assertContains( 'append_to_post', $fields['internal'] );
		$this->assertArrayHasKey( 'file', $fields, var_export( $fields, true ) );
		$this->assertContains( 'files', $fields['file'] );

		wp_reset_postdata();
	}

	public function test_custom_file_shortcode_is_persisted_as_the_rendered_file_field() {
		$page_id         = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$GLOBALS['post'] = get_post( $page_id );
		setup_postdata( $GLOBALS['post'] );

		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array( 'enable_recaptcha_protection' => 'off' )
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout'            => 'media',
				'post_id'                => $page_id,
				'suppress_default_fields' => true,
			),
			'[input type="file" name="my-file"]'
		);

		$this->assertStringContainsString( 'name="my-file[]"', $form );
		preg_match( '/name="ff" value="([a-f0-9]+)"/', $form, $hash_match );
		$fields = $this->fu->_get_fields_for_form( $page_id, $hash_match[1] );

		$this->assertSame( array( 'my-file' ), $fields['file'] );
		$this->assertNotContains( 'my-file', isset( $fields['meta'] ) ? $fields['meta'] : array() );

		wp_reset_postdata();
	}

	public function test_unnamed_file_shortcode_uses_the_default_files_name() {
		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array( 'enable_recaptcha_protection' => 'off' )
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout'            => 'media',
				'suppress_default_fields' => true,
			),
			'[input type="file"]'
		);

		$this->assertStringContainsString( 'name="files[]"', $form );
	}

	public function test_author_setting_renders_guest_byline_when_default_fields_are_suppressed() {
		$page_id         = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$GLOBALS['post'] = get_post( $page_id );
		setup_postdata( $GLOBALS['post'] );

		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array(
				'enable_recaptcha_protection' => 'off',
				'show_author'                 => 'on',
			)
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout'            => 'post',
				'post_id'                => $page_id,
				'suppress_default_fields' => true,
			)
		);

		$this->assertStringContainsString( 'name="post_author"', $form );
		$this->assertStringContainsString( 'Author</label>', $form );

		preg_match( '/name="ff" value="([a-f0-9]+)"/', $form, $hash_match );
		$fields = $this->fu->_get_fields_for_form( $page_id, $hash_match[1] );
		$this->assertSame( array( 'post_author' ), $fields['author'] );

		wp_reset_postdata();
	}

	public function test_disabled_author_setting_does_not_render_guest_byline() {
		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array(
				'enable_recaptcha_protection' => 'off',
				'show_author'                 => 'off',
			)
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout'            => 'post',
				'suppress_default_fields' => true,
			)
		);

		$this->assertStringNotContainsString( 'name="post_author"', $form );
	}

	public function test_image_form_without_parent_omits_parent_nonce_and_append_flag() {
		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array(
				'enable_recaptcha_protection' => 'off',
				'suppress_default_fields'      => 'off',
			)
		);

		$form = $this->fu->upload_form(
			array(
				'form_layout' => 'image',
				'post_id'     => 0,
			)
		);

		$this->assertStringNotContainsString( 'name="fu_parent_nonce"', $form );
		$this->assertStringNotContainsString( 'name="append_to_post"', $form );
		$this->assertStringContainsString( 'name="files[]"', $form );
	}
}
