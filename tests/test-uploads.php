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
		$tmp_name = wp_tempnam( 'caption.png' );
		file_put_contents( $tmp_name, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' ) );
		$result = array();

		try {
			$this->fu->allowed_mime_types = array( 'png' => 'image/png' );
			$_POST[ $field ]              = wp_slash( 'A < b data-x="1" >bold< /b > & "c"' );
			$_FILES['files']              = array(
				'name'     => array( 'caption.png' ),
				'type'     => array( 'image/png' ),
				'tmp_name' => array( $tmp_name ),
				'error'    => array( 0 ),
				'size'     => array( filesize( $tmp_name ) ),
			);

			$result     = $this->fu->_upload_files();
			$attachment = get_post( $result['media_ids'][0] );

			$this->assertTrue( $result['success'] );
			$this->assertSame( 'A &lt; b data-x="1" &gt;bold&lt; /b &gt; &amp; "c"', $attachment->post_content );
			$this->assertSame( 'A &lt; b data-x="1" &gt;bold&lt; /b &gt; &amp; "c"', $attachment->post_excerpt );
		} finally {
			foreach ( $result['media_ids'] ?? array() as $media_id ) {
				wp_delete_attachment( $media_id, true );
			}
			if ( file_exists( $tmp_name ) ) {
				unlink( $tmp_name );
			}
		}
	}
}
