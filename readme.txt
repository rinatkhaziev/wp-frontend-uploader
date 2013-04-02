=== Frontend Uploader ===
Contributors: rinatkhaziev, rfzappala, danielbachhuber
Tags: frontend, image, images, media, uploader, upload, video, audio, photo, photos, picture, pictures, file
Requires at least: 3.3
Tested up to: 3.6-alpha-23879
Stable tag: 0.4.1

This plugin allows your visitors to upload User Generated Content (media and posts/custom-post-types with media).

== Description ==

This plugin gives you an ability to easily accept, moderate and publish user generated content (currently, there are 3 modes: media, post, post + media). The plugin allows you to create a front end form with multiple fields (easily customizable with shortcodes). You can limit which MIME-types are supported for each field. All of the submissions are safely held for moderation in Media/Post/Custom Post Types menu under a special tab "Manage UGC". Review, moderate and publish. It's that easy!

This plugin supports multiple uploads for modern browsers (sorry, no IE). Multiple file uploads are enabled for default form. To use it in your custom shortcode add multiple="" attribute to file shortcode.

Here's example of default form (you don't need to enter all that if you want to use default form, just use [fu-upload-form]):

[fu-upload-form class="your-class" title="Upload your media"]
[textarea name="caption" class="textarea" id="ug_caption" description="Description (optional)"]
[input type="file" name="photo" id="ug_photo" class="required" description="Your Photo" multiple=""]
[input type="submit" class="btn" value="Submit"]
[/fu-upload-form]

By default plugin allows all MIME-types that are whitelisted in WordPress. However, there's a filter if you need to add some exotic MIME-type. Refer to Other notes -> Configuration filters.

= New in v0.4 =

Now your visitors are able to upload not only media, but guest posts as well! 
Use [fu-upload-form form_layout="post_image"] to get default form to upload post content and images
Use [fu-upload-form form_layout="post"] to get default form to upload post content

You can also manage UGC for selected custom post types (Please refer to the plugin's settings page). By default, UGC is enabled for posts and attachments. If you want to be able to get any other post types UGC submissions just select desired post types at the plugin's settings page, and pass post_type='my_post_type' to the [fu-upload-form] shortcode

= Translations: =

* Se habla español (Spanish) (props gastonbesada)
* Мы говорим по-русски (Russian)
* Nous parlons français (French) (props dapickboy)
* Nous parlons français (Canadian French) (props rfzappala)
[Fork the plugin on Github](https://github.com/rinatkhaziev/wp-frontend-uploader/)

== Installation ==

1. Upload `frontend-uploader` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Tweak the plugin's settings in: Settings -> Frontend Uploader Settings
1. Use the following shortcode in post or page: [fu-upload-form]
1. Moderate uploaded files in Media -> Manage UGC menu
1. Moderate user posts in Posts -> Manage UGC

== Screenshots ==

1. Screenshot of plugin's UI (It's looks like standard media list table, with slightly better Parent column and additional row action: "Approve")

== Configuration Filters ==

= fu_allowed_mime_types =

Allows you to add your custom MIME-types. Please note that there might be multiple MIME types per file extension.

`add_filter( 'fu_allowed_mime_types', 'my_fu_allowed_mime_types' );
function my_fu_allowed_mime_types( $mime_types ) {
	$mp3_mimes = array( 'audio/mpeg', 'audio/x-mpeg', 'audio/mp3', 'audio/x-mp3', 'audio/mpeg3', 'audio/x-mpeg3', 'audio/mpg', 'audio/x-mpg', 'audio/x-mpegaudio' );
	foreach( $mp3_mimes as $mp3_mime ) {
		$mime = $mp3_mime;
		preg_replace("/[^0-9a-zA-Z ]/", "", $mp3_mime );
		$mime_types['mp3|mp3_' . $mp3_mime ] = $mime;
	}
	return $mime_types;
}`

= fu_after_upload =

`add_action( 'fu_after_upload', 'my_fu_after_upload' );

function my_fu_after_upload( $attachment_ids ) {
	// do something with freshly uploaded files
	// This happens on POST request, so $_POST will also be available for you
}`

= fu_additional_html =

Allows you to add additional HTML to form

`add_action('fu_additional_html', 'my_fu_additional_html' );

function my_fu_additional_html() {
?>
<input type="hidden" name="my_custom_param" value="something" />
<?php
}`

== Frequently Asked Questions ==


== Changelog ==

= 0.4 (Mar 30, 2013) =

* Ability to submit posts+files via [fu-upload-form form_layout="post_image|post|image"] where form_layout might be "post_image", "post", or "image". Defaults to "image". /props rfzappala
* Ability to submit and manage custom post types
* Ability to use visual editor for textareas
* Bugfixes /props danielbachhuber
* Under the hood improvements

= 0.3.1 (Jan 3, 2013) =

* Remove closure as it produces Fatal Error in PHP < 5.3

= 0.3 (Jan 2, 2013) =

* Fully compatible with 3.5 Media Manager: automatically adds id of approved picture to the gallery.
* Fix IE upload issue, props mcnasby
* fu_allowed_mime_types filter is working now

= 0.2.5 (Oct 18, 2012) =

* Fix potential Fatal Error on activation

= 0.2.4 (Oct 10, 2012) =

* Fix compatibility issue for upcoming WP 3.5

= 0.2.3 (Oct 5, 2012) =

* Massive UI Cleanup: added minimal css, and pretty notices
* Plugin settings: ability to notify site admins of new file uploads
* Added French translation. Props dapickboy

= 0.2.2 (Sep 2, 2012) =

* Hardened security. Even if user for some reason will allow PHP file uploads, they won't be uploaded.
* Added Russian translation
* Added translations for jquery.validate plugin

= 0.2.1.1 (August 30, 2021) =

* Added missing localization strings

= 0.2.1 (August 30, 2012) =

* Added l10n support, added Spanish translation. Props gastonbesada

= 0.2 (August 15, 2012) =

* Utilized support of "multiple" file tag attribute in modern browsers, that allows multiple files upload at once ( no IE )

= 0.1.2 (June 6, 2012) =

* Added localization strings

= 0.1.1 (May 23, 2012) =

* Feature: allow form customization
* Feature: re-attach attachment to different post

= 0.1 (May 21, 2012) =

* Initial release and poorly written readme
