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
}
