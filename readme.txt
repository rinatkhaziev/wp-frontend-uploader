=== Frontend Uploader ===
Contributors: rinatkhaziev, gastonbesada
Tags: frontend, image, images, media, uploader, upload, video, audio, photo, photos, picture, pictures, file
Requires at least: 3.1
Tested up to: 3.4
Stable tag: 0.2.1

This plugin allows your visitors to upload User Generated Content.

== Description ==

This plugin is useful if you want to power up your site with user generated content and give your users ability to easily upload it. Essentially, the plugin is a customizeable upload form that adds files with allowed MIME-type to your WordPress Media Library under a special tab "Manage UGC". There you can moderate your user submissions (cause, you know, you'd better moderate 'em):

* Approve
* Delete
* Re-attach to other post/page/custom-post-type

[Fork the plugin on Github](https://github.com/rinatkhaziev/wp-frontend-uploader/)

== Installation ==

1. Upload `frontend-uploader` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use the following shortcode in post or page: [fu-upload-form]
1. You can moderate uploaded files in Media -> Manage UGC menu

== Shortcode example ==

Here's example of default form (you don't need to enter all that if you want to use default form, just use [fu-upload-form]):

[fu-upload-form class="your-class" title="Upload your media"]
[textarea name="caption" class="textarea" id="ug_caption" description="Description (optional)"]	   
[input type="file" name="photo" id="ug_photo" class="required" description="Your Photo" multiple=""]
[input type="submit" class="btn" value="Submit"]
[/fu-upload-form]

== Screenshots ==

1. Screenshot of plugin's UI (It's looks like standard media list table, with slightly better Parent column and additional row action: "Approve")

== Configuration Filters ==

= fu_allowed_mime_types =

By default plugin only allows GIF, PNG, JPG images but you can use this filter to pass additional MIME types like that:

add_filter( 'fu_allowed_mime_types', 'my_fu_allowed_mime_types' );
function my_fu_allowed_mime_types( $mime_types ) {
	$mime_types[] = 'image/tiff';
	return $mime_types;
}

= fu_after_upload =

add_action( 'fu_after_upload', 'my_fu_after_upload' );

function my_fu_after_upload( $attachment_ids ) {
	// do something with freshly uploaded files
	// This happens on POST request, so $_POST will also be available for you
}

= fu_additional_html =

Allows you to add additional HTML to form

add_action('fu_additional_html', 'my_fu_additional_html' );

function my_fu_additional_html() {
?>
<input type="hidden" name="my_custom_param" value="something" />
<?php 
}

== Changelog ==

= 0.2 (August 15, 2012) =

* Utilized support of "multiple" file tag attribute in modern browsers, that allows multiple files upload at once ( no IE )

= 0.1.2 (June 6, 2012) =

* Added localization strings 

= 0.1.1 (May 23, 2012) =

* Feature: allow form customization
* Feature: re-attach attachment to different post

= 0.1 (May 21, 2012) =

* Initial release and poorly written readme