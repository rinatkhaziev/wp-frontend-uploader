<?php
/**
 * Post-submission integration tests.
 */

class Frontend_Uploader_Submissions_Test extends Frontend_Uploader_Test_Case {
	public function set_up() {
		parent::set_up();

		$this->fu->settings = array_merge(
			(array) $this->fu->settings,
			array(
				'auto_approve_user_files' => 'off',
				'auto_approve_any_files'  => 'off',
				'enabled_post_types'      => array( 'post' => 'Posts' ),
			)
		);
	}

	public function test_post_submission_is_private_and_sanitized() {
		$category_id = self::factory()->category->create();
		$_POST       = array(
			'post_type'     => 'post',
			'post_title'    => '<b>Submission title</b>',
			'post_content'  => '<script>alert(1)</script><p>Allowed content</p>',
			'post_category' => $category_id . ',not-a-number',
			'post_author'   => 'Visitor <script>alert(1)</script>',
		);

		$result = $this->fu->_upload_post();
		$post   = get_post( $result['post_id'] );

		$this->assertTrue( $result['success'] );
		$this->assertInstanceOf( WP_Post::class, $post );
		$this->assertSame( 'private', $post->post_status );
		$this->assertSame( 'Submission title', $post->post_title );
		$this->assertStringNotContainsString( '<script', $post->post_content );
		$this->assertStringContainsString( '<p>Allowed content</p>', $post->post_content );
		$this->assertContains( $category_id, wp_get_post_categories( $post->ID ) );
		$this->assertSame( 0, (int) $post->post_author );
		$this->assertSame( 'Visitor', get_post_meta( $post->ID, 'author_name', true ) );
	}

	public function test_auto_approved_submission_is_published() {
		$this->fu->settings['auto_approve_any_files'] = 'on';
		$_POST = array(
			'post_type'  => 'post',
			'post_title' => 'Published submission',
		);

		$result = $this->fu->_upload_post();

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'publish', get_post_status( $result['post_id'] ) );
	}

	public function test_non_scalar_submission_values_are_not_persisted() {
		$_POST = array(
			'post_type'    => array( 'page' ),
			'post_title'   => array( 'Unsafe title' ),
			'post_content' => array( '<p>Unsafe content</p>' ),
			'post_author'  => array( 'administrator' ),
		);

		$result = $this->fu->_upload_post();
		$post   = get_post( $result['post_id'] );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'post', $post->post_type );
		$this->assertSame( 'Untitled post submission', $post->post_title );
		$this->assertSame( '', $post->post_content );
		$this->assertSame( '', get_post_meta( $post->ID, 'author_name', true ) );
	}

	public function test_disallowed_post_type_falls_back_to_post() {
		register_post_type( 'fu_book', array( 'public' => true ) );
		try {
			$_POST = array(
				'post_type'  => 'fu_book',
				'post_title' => 'Book submission',
			);

			$result = $this->fu->_upload_post();

			$this->assertSame( 'post', get_post_type( $result['post_id'] ) );
		} finally {
			unregister_post_type( 'fu_book' );
		}
	}

	public function test_auto_approval_for_logged_in_users_requires_the_setting() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertFalse( $this->fu->_is_public() );

		$this->fu->settings['auto_approve_user_files'] = 'on';
		$this->assertTrue( $this->fu->_is_public() );
	}
}
