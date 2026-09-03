<?php
/**
 * File-upload behavior tests.
 */

class Frontend_Uploader_Uploads_Test extends Frontend_Uploader_Test_Case {
	private function create_png_upload( $name ) {
		$tmp_name = wp_tempnam( $name );
		$png      = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' );
		file_put_contents( $tmp_name, $png );

		return array(
			'name'     => $name,
			'type'     => 'image/png',
			'tmp_name' => $tmp_name,
			'error'    => 0,
			'size'     => filesize( $tmp_name ),
		);
	}

	private function delete_uploaded_media( $result, $temporary_files ) {
		foreach ( isset( $result['media_ids'] ) ? $result['media_ids'] : array() as $media_id ) {
			wp_delete_attachment( $media_id, true );
		}

		foreach ( $temporary_files as $temporary_file ) {
			if ( file_exists( $temporary_file ) ) {
				unlink( $temporary_file );
			}
		}
	}

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

	public function test_guest_author_is_saved_on_every_successful_attachment() {
		$first  = $this->create_png_upload( 'first.png' );
		$second = $this->create_png_upload( 'second.png' );
		$result = array();

		try {
			$this->fu->allowed_mime_types = array( 'png' => 'image/png' );
			$_POST['post_author']          = 'Visitor <script>alert(1)</script>';
			$_FILES['files']               = array(
				'name'     => array( $first['name'], $second['name'] ),
				'type'     => array( $first['type'], $second['type'] ),
				'tmp_name' => array( $first['tmp_name'], $second['tmp_name'] ),
				'error'    => array( $first['error'], $second['error'] ),
				'size'     => array( $first['size'], $second['size'] ),
			);

			$result = $this->fu->_upload_files();

			$this->assertTrue( $result['success'] );
			$this->assertCount( 2, $result['media_ids'] );
			foreach ( $result['media_ids'] as $media_id ) {
				$this->assertSame( 'Visitor', get_post_meta( $media_id, 'author_name', true ) );
				$this->assertSame( 0, (int) get_post( $media_id )->post_author );
			}
		} finally {
			$this->delete_uploaded_media( $result, array( $first['tmp_name'], $second['tmp_name'] ) );
		}
	}

	public function test_guest_author_is_saved_on_successful_attachment_in_partial_batch() {
		$upload = $this->create_png_upload( 'successful.png' );
		$result = array();

		try {
			$this->fu->allowed_mime_types = array( 'png' => 'image/png' );
			$_POST['post_author']          = 'Partial Visitor';
			$_FILES['files']               = array(
				'name'     => array( $upload['name'], 'failed.png' ),
				'type'     => array( $upload['type'], 'image/png' ),
				'tmp_name' => array( $upload['tmp_name'], '' ),
				'error'    => array( $upload['error'], UPLOAD_ERR_INI_SIZE ),
				'size'     => array( $upload['size'], 0 ),
			);

			$result = $this->fu->_upload_files();

			$this->assertFalse( $result['success'] );
			$this->assertCount( 1, $result['media_ids'] );
			$this->assertSame( UPLOAD_ERR_INI_SIZE, $result['errors']['fu-error-media'][0]['code'] );
			$this->assertSame( 'Partial Visitor', get_post_meta( $result['media_ids'][0], 'author_name', true ) );
		} finally {
			$this->delete_uploaded_media( $result, array( $upload['tmp_name'] ) );
		}
	}

	public function test_guest_author_is_saved_on_combined_post_and_media_submission() {
		$upload      = $this->create_png_upload( 'combined.png' );
		$post_result = array();
		$file_result = array();

		try {
			$this->fu->settings['enabled_post_types'] = array( 'post' => 'Posts' );
			$this->fu->allowed_mime_types             = array( 'png' => 'image/png' );
			$_POST                                    = array(
				'post_type'   => 'post',
				'post_title'  => 'Combined submission',
				'post_author' => 'Combined Visitor',
			);
			$_FILES['files']                          = $upload;

			$post_result = $this->fu->_upload_post();
			$file_result = $this->fu->_upload_files( $post_result['post_id'] );

			$this->assertTrue( $post_result['success'] );
			$this->assertTrue( $file_result['success'] );
			$this->assertSame( 'Combined Visitor', get_post_meta( $post_result['post_id'], 'author_name', true ) );
			$this->assertCount( 1, $file_result['media_ids'] );
			$this->assertSame( 'Combined Visitor', get_post_meta( $file_result['media_ids'][0], 'author_name', true ) );
		} finally {
			$this->delete_uploaded_media( $file_result, array( $upload['tmp_name'] ) );
			if ( isset( $post_result['post_id'] ) && ! is_wp_error( $post_result['post_id'] ) ) {
				wp_delete_post( $post_result['post_id'], true );
			}
		}
	}
}
