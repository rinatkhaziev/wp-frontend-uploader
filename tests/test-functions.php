<?php
/**
 * Procedural helper tests.
 */

class Frontend_Uploader_Functions_Test extends Frontend_Uploader_Test_Case {
	public function test_file_array_normalizes_multiple_uploads() {
		$_FILES['uploads'] = array(
			'name'     => array( 'first' => 'one.jpg', 'second' => 'two.jpg' ),
			'type'     => array( 'first' => 'image/jpeg', 'second' => 'image/jpeg' ),
			'tmp_name' => array( 'first' => '/tmp/one', 'second' => '/tmp/two' ),
			'error'    => array( 'first' => 0, 'second' => UPLOAD_ERR_NO_FILE ),
			'size'     => array( 'first' => 100, 'second' => 0 ),
		);

		$files = fu_get_file_array();

		$this->assertSame(
			array(
				'uploads' => array(
					'first'  => array(
						'name'     => 'one.jpg',
						'type'     => 'image/jpeg',
						'tmp_name' => '/tmp/one',
						'error'    => 0,
						'size'     => 100,
					),
					'second' => array(
						'name'     => 'two.jpg',
						'type'     => 'image/jpeg',
						'tmp_name' => '/tmp/two',
						'error'    => UPLOAD_ERR_NO_FILE,
						'size'     => 0,
					),
				),
			),
			$files
		);
	}

	public function test_file_array_preserves_single_upload_shape() {
		$_FILES['upload'] = array(
			'name'     => 'one.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/one',
			'error'    => 0,
			'size'     => 100,
		);

		$this->assertSame( $_FILES, fu_get_file_array() );
	}
}
