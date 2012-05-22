=== Frontend Uploader ===
Contributors: rinatkhaziev
Tags: frontend, image, media, uploader
Requires at least: 3.1
Tested up to: 3.4
Stable tag: 0.1

This plugin allows your visitors to upload User Generated Content.

== Description ==

This plugin is useful if you want to power up your site with user content and give your visitors ability to easily upload content.  

== Installation ==

1. Upload `frontend-uploader` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the following shortcode in post or page: [fu-upload-form]
1. You can moderate uploaded files in Media -> Manage UGC menu

== Configuration Filters ==

= fu_allowed_mime_types =

By default plugin only allows GIF, PNG, JPG images but you can use this filter to pass additional MIME types like that:

add_filter( 'fu_allowed_mime_types', function( $mime_types ) {
	$mime_types[] = 'image/tiff';
	return $mime_types;
} );

= fu_after_upload =

add_action( 'fu_after_upload', function( $attachment_ids ) {
	// do something with freshly uploaded files
	// This happens on POST request, so $_POST will also be available for you
} );

= fu_additional_html =

Allows you to add additional HTML to form

add_action('fu_additional_html', function() {
?>
<input type="hidden" name="my_custom_param" value="something" />
<?php 
});

== Changelog ==

= 0.1 (May 21, 2012)
* Initial release and poorly written readme

[Fork the plugin on Github](https://github.com/rinatkhaziev/)