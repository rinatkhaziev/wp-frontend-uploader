<?php
/**
 * File-upload behavior tests.
 */

class Frontend_Uploader_Uploads_Test extends Frontend_Uploader_Test_Case {
	public function test_no_files_is_a_successful_no_op() {
		$this->assertSame(
			array(
				'success'   => true,
				'media_ids' => array(),
				'errors'    => array(),
			),
			$this->fu->_upload_files()
		);
	}

	public function test_empty_file_input_is_a_successful_no_op() {
		$_FILES['upload'] = array(
			'name'     => 'empty.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_NO_FILE,
			'size'     => 0,
		);

		$result = $this->fu->_upload_files();

		$this->assertTrue( $result['success'] );
		$this->assertSame( array(), $result['media_ids'] );
		$this->assertSame( array(), $result['errors'] );
		$this->assertFalse( has_filter( 'upload_mimes', array( $this->fu, '_get_mime_types' ) ) );
	}

	public function test_server_upload_error_is_reported_and_filter_is_removed() {
		$_FILES['upload'] = array(
			'name'     => '../broken.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '',
			'error'    => UPLOAD_ERR_INI_SIZE,
			'size'     => 0,
		);

		$result = $this->fu->_upload_files();

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'broken.jpg', $result['errors']['fu-error-media'][0]['name'] );
		$this->assertSame( UPLOAD_ERR_INI_SIZE, $result['errors']['fu-error-media'][0]['code'] );
		$this->assertFalse( has_filter( 'upload_mimes', array( $this->fu, '_get_mime_types' ) ) );
	}

	public function test_suspicious_file_is_rejected_before_media_creation() {
		$tmp_name = wp_tempnam( 'suspicious.jpg' );
		file_put_contents( $tmp_name, '<?php eval( base64_decode( $payload ) );' );

		try {
			$this->fu->allowed_mime_types = array( mime_content_type( $tmp_name ) );
			$_FILES['upload'] = array(
				'name'     => '../../suspicious.jpg',
				'type'     => 'image/jpeg',
				'tmp_name' => $tmp_name,
				'error'    => 0,
				'size'     => filesize( $tmp_name ),
			);

			$result = $this->fu->_upload_files();

			$this->assertFalse( $result['success'] );
			$this->assertSame( array(), $result['media_ids'] );
			$this->assertSame( 'suspicious.jpg', $result['errors']['fu-suspicious-file'][0]['name'] );
			$this->assertFalse( has_filter( 'upload_mimes', array( $this->fu, '_get_mime_types' ) ) );
		} finally {
			unlink( $tmp_name );
		}
	}

	/**
	 * Request fields the attachment caption is read from.
	 *
	 * @return array[]
	 */
	public function data_caption_fields() {
		return array(
			'caption'                    => array( 'caption' ),
			'post_content as a fallback' => array( 'post_content' ),
		);
	}

	/**
	 * Tag-like text that sanitize_text_field() leaves must not come back as markup.
	 *
	 * @dataProvider data_caption_fields
	 *
	 * @param string $field Request field holding the caption.
	 */
	public function test_caption_is_stored_as_text_that_kses_cannot_rebuild_into_markup( $field ) {
		$_POST[ $field ] = wp_slash( 'A < b data-x="1" >bold< /b > & "c"' );

		list( $result, $tmp_name ) = $this->upload_png( 'caption.png' );

		try {
			$this->assertTrue( $result['success'] );

			$attachment = get_post( $result['media_ids'][0] );
			$this->assertSame( 'A &lt; b data-x="1" &gt;bold&lt; /b &gt; &amp; "c"', $attachment->post_content );
			$this->assertSame( 'A &lt; b data-x="1" &gt;bold&lt; /b &gt; &amp; "c"', $attachment->post_excerpt );
		} finally {
			$this->delete_upload( $result, $tmp_name );
		}
	}

	/**
	 * Submitted attachment titles and what they're stored as.
	 *
	 * @return array[]
	 */
	public function data_attachment_titles() {
		return array(
			'tag-like text'            => array( 'A < b >bold< /b > & "c"', 'A &lt; b &gt;bold&lt; /b &gt; &amp; "c"' ),
			'an entity kses keeps'     => array( 'It&apos;s', 'It&apos;s' ),
			'nothing after sanitizing' => array( '<b></b>', 'title' ),
		);
	}

	/**
	 * The attachment title is stored as the submitted text, or the filename when nothing is left of it.
	 *
	 * @dataProvider data_attachment_titles
	 *
	 * @param string $submitted Submitted title.
	 * @param string $expected  Stored title.
	 */
	public function test_attachment_title_is_stored_as_text( $submitted, $expected ) {
		$_POST['post_title'] = wp_slash( $submitted );

		list( $result, $tmp_name ) = $this->upload_png( 'title.png' );

		try {
			$this->assertTrue( $result['success'] );
			$this->assertSame( $expected, get_post( $result['media_ids'][0] )->post_title );
		} finally {
			$this->delete_upload( $result, $tmp_name );
		}
	}

	/**
	 * A title taken from the filename has no markup to encode: sanitize_file_name() strips it.
	 */
	public function test_attachment_title_from_filename_has_no_markup() {
		list( $result, $tmp_name ) = $this->upload_png( '< b >x< /b > & y.png' );

		try {
			$this->assertTrue( $result['success'] );
			$this->assertSame( 'b-x-b-y', get_post( $result['media_ids'][0] )->post_title );
		} finally {
			$this->delete_upload( $result, $tmp_name );
		}
	}

	/**
	 * Uploads a 1×1 PNG through Frontend Uploader.
	 *
	 * @param string $name File name.
	 * @return array The upload result and the temporary file path.
	 */
	private function upload_png( $name ) {
		$tmp_name = wp_tempnam( $name );
		file_put_contents( $tmp_name, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' ) );

		$this->fu->allowed_mime_types = array( 'png' => 'image/png' );
		$_FILES['files']              = array(
			'name'     => array( $name ),
			'type'     => array( 'image/png' ),
			'tmp_name' => array( $tmp_name ),
			'error'    => array( 0 ),
			'size'     => array( filesize( $tmp_name ) ),
		);

		return array( $this->fu->_upload_files(), $tmp_name );
	}

	/**
	 * Deletes the media and temporary file left by `upload_png()`.
	 *
	 * @param array  $result   Upload result.
	 * @param string $tmp_name Temporary file path.
	 */
	private function delete_upload( $result, $tmp_name ) {
		foreach ( $result['media_ids'] ?? array() as $media_id ) {
			wp_delete_attachment( $media_id, true );
		}

		if ( file_exists( $tmp_name ) ) {
			unlink( $tmp_name );
		}
	}
}
