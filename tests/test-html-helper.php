<?php
/**
 * HTML helper behavior tests.
 */

class Frontend_Uploader_Html_Helper_Test extends Frontend_Uploader_Test_Case {
	public function test_element_escapes_content_and_rejects_disallowed_tags() {
		$helper = new Html_Helper();
		$element = $helper->element( 'p', 'Unsafe <script>alert(1)</script>' );

		$this->assertStringContainsString( 'Unsafe &lt;script&gt;alert(1)&lt;/script&gt;', $element );
		$this->assertStringNotContainsString( '<script>', $element );
		$this->assertNull( $helper->element( 'script', 'alert(1)' ) );
	}

	public function test_attributes_drop_event_handlers_and_unpaired_aria_required() {
		$helper     = new Html_Helper();
		$attributes = $helper->_format_attributes(
			array(
				'class'         => 'field<script>',
				'onclick'       => 'alert(1)',
				'required'      => false,
				'aria-required' => 'true',
			)
		);

		$this->assertStringContainsString( "class='field&lt;script&gt;'", $attributes );
		$this->assertStringNotContainsString( 'onclick', $attributes );
		$this->assertStringNotContainsString( 'required', $attributes );
	}

	public function test_required_attribute_keeps_aria_required() {
		$helper     = new Html_Helper();
		$attributes = $helper->_format_attributes(
			array(
				'required'      => 'required',
				'aria-required' => 'true',
			)
		);

		$this->assertStringContainsString( "required='required'", $attributes );
		$this->assertStringContainsString( "aria-required='true'", $attributes );
	}

	public function test_select_marks_the_requested_default() {
		$helper = new Html_Helper();
		$select = $helper->input(
			'select',
			'colour',
			array(
				'blue' => 'Blue',
				'red'  => 'Red',
			),
			array( 'default' => 'red' )
		);

		$this->assertStringContainsString( '<select name="colour">', $select );
		$this->assertStringContainsString( "value='red' selected='selected'", $select );
		$this->assertStringNotContainsString( "value='blue' selected='selected'", $select );
	}

	public function test_link_requires_a_valid_url_and_escapes_the_title() {
		$helper = new Html_Helper();

		$this->assertNull( $helper->a( 'javascript:alert(1)', 'Unsafe' ) );
		$this->assertStringContainsString( 'Safe &lt;title&gt;', $helper->a( 'https://example.com/', 'Safe <title>' ) );
	}
}
