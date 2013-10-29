=== Frontend Uploader ===
Contributors: rinatkhaziev, danielbachhuber, jtrees
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=rinat%2ekhaziev%40gmail%2ecom
Tags: frontend, image, images, media, uploader, upload, video, audio, photo, photos, picture, pictures, file, user generated content
Requires at least: 3.3
Tested up to: 3.8-alpha
Stable tag: 0.6

This plugin allows your visitors to upload User Generated Content (media and posts/custom-post-types with media).

== Description ==

This plugin gives you an ability to easily accept, moderate and publish user generated content (currently, there are 3 modes: media, post, post + media). The plugin allows you to create a front end form with multiple fields (easily customizable with shortcodes, refer to FAQ). 
All of the submissions are safely held for moderation in Media/Post/Custom Post Types menu under a special tab "Manage UGC". Review, moderate and publish. It's that easy!

This plugin supports multiple uploads for modern browsers. Multiple file uploads are enabled for default form. To use it in your custom shortcode add multiple="" attribute to file shortcode.

If you want to customize your form, please refer to FAQ section.

**By default the plugin allows all file types that are whitelisted in WordPress.** However, you can whitelist additional common file types on the settings page. If you're comfortable with theme development, there's a filter that'll allow you to add some exotic MIME-type. Be sure to check out FAQ to get a grasp on how to customize the upload form with actions and filters.

= New in v0.5 =

You can choose what type of files you allow your visitors to upload from Frontend Uploader Settings

= New in v0.4 =

Now your visitors are able to upload not only media, but guest posts as well!
Use [fu-upload-form form_layout="post_image"] to get default form to upload post content and images
Use [fu-upload-form form_layout="post"] to get default form to upload post content

You can also manage UGC for selected custom post types (Please refer to the plugin's settings page). By default, UGC is enabled for posts and attachments. If you want to be able to get any other post types UGC submissions just select desired post types at the plugin's settings page, and pass post_type='my_post_type' to the [fu-upload-form] shortcode.

= Translations: =

* Se habla español (Spanish) (props Rafael Calzada, gastonbesada)
* Мы говорим по-русски (Russian)
* Nous parlons français (French) (props dapickboy)
* Nous parlons français (Canadian French) (props rfzappala)
* Vi snakker norsk (Norwegian) (props André Langseth)
* Wir sprechen Deutsch (German) (props Joshua Trees)

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

== Frequently Asked Questions ==

= Shortcode parameters =

The [fu-upload-form] shortcode has several parameters that can modify its behavior:

1. 'title' => Headline that will be displayed before the form
1. 'class' => HTML class of the form, defaults to 'validate'. If you want your form being validated - do not remove validate class
1. 'category' => ID of category the post should be attached (only in post or post+image mode). The category should be whitelisted in the settings
1. 'success_page' => URL to redirect on successful submission, defaults to the URL where the form is being displayed
1. 'form_layout' => There are three different modes: post, post_image, image. Default is image
1. 'post_id' => ID of the post the image should be attached to. Defaults to current post id
1. 'post_type' => Any registered whitelisted post type. Defaults to 'post'. Works only in post and post+image modes.

= Example of default media upload form =
Here's example of default form (*you don't need to enter all that if you want to use default form, just use [fu-upload-form]*):

`[fu-upload-form class="your-class" title="Upload your media"]
[input type="text" name="post_title" id="title" class="required" description="Title" multiple=""]
[textarea name="post_content" class="textarea" id="ug_caption" description="Description (optional)"]
[input type="file" name="photo" id="ug_photo" class="required" description="Your Photo" multiple=""]
[input type="submit" class="btn" value="Submit"]
[/fu-upload-form]`

= Help, I pasted in the above shortcode and now my fields are duplicated =
As stated in previous answer, you don't need to put inner contents if you only need default form fields.
E.g. `[fu-upload-form class="your-class" title="Upload your media"]` will be enough to render the default form.
You can suppress rendering of default form fields with "Suppress default fields" checkbox in settings

= I want to customize my form =
You can include additional elements with a set of shortcodes
[input type="text" name="post_title" id="title" class="required" description="Title" multiple=""]
[select name="foo" class="select" id="ug_select" description="Pick a fruit" values="Apple,Banana,Cherry"]
[textarea name="post_content" class="textarea" id="ug_caption" description="Description (optional)"]

= I want to be allow users to upload mp3, psd, or any other file restricted by default. =
You are able to do that within Frontend Uploader Settings admin page. The settings there cover the most popular extensions/MIME-types.
The trick is that the same file might have several different mime-types based on setup of server/client.
If you're experiencing any issues, you can set WP_DEBUG to true in your wp-config.php or put
`add_filter( 'fu_is_debug', '__return_true' );` in your theme's functions.php to see what MIME-types you are having troubles with.

[FileExt](http://filext.com/) is a good place to find MIME-types for specific file extension.

Let's say we want to be able to upload 3gp media files.

First we look up all MIME-types for 3gp: http://filext.com/file-extension/3gp

Now that we have all possible MIME-types for .3gp, we can allow the files to be uploaded.

Following code whitelists 3gp files, if it makes sense to you, you can modify it for other extensions/mime-types.
If it confuses you, please don't hesitate to post on support forum.
Put this in your theme's functions.php
`add_filter( 'fu_allowed_mime_types', 'my_fu_allowed_mime_types' );
function my_fu_allowed_mime_types( $mime_types ) {
	// Array of 3gp mime types
	// From http://filext.com (there might be more)
	$mimes = array( 'audio/3gpp', 'video/3gpp' );
	// Iterate through all mime types and add this specific mime to allow it
	foreach( $mimes as $mime ) {
		// Preserve the mime_type
		$orig_mime = $mime;
		// Leave only alphanumeric characters (needed for unique array key)
		preg_replace("/[^0-9a-zA-Z ]/", "", $mime );
		// Workaround for unique array keys
		// If you-re going to modify it for your files
		// Don't forget to change extension in array key
		// E.g. $mime_types['pdf|pdf_' . $mime ] = $orig_mime
		$mime_types['3gp|3gp_' . $mime ] = $orig_mime;
	}
	return $mime_types;
}`

= There's no captcha! =
The plugin runs on multiple websites doing millions of pageviews daily. So far I haven't received a single report regarding spam. 
If you do get spam, please report it in support forums.

= Configuration Filters =

= fu_manage_permissions =

By default Frontend Uploader could be managed with 'edit_posts' capability, if you want to change permissions, this is the right filter
`add_filter( 'fu_manage_permissions', create_function( '$cap', 'return "edit_others_posts"; ) );`

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

= fu_is_debug =

If you're experiencing issues with upload it might be due to server misconfiguration, enabling debug mode will give you more detailed error messages

`add_filter( 'fu_is_debug', '__return_true' );`

== Changelog ==

= 0.6 (Oct 29, 2013) =
* Updated German translation
* Updated Spanish translation
* Hidden inputs are no longer getting wrapped in label and div
* Added an option to set a default file name 
* Fixed category attribute of shortcode

= 0.5.9 (Aug 28th, 2013) =
* Introduced setting to disable default fields
* Fixed bug with inability to uncheck all extra file types

= 0.5.8 (July 25th, 2013) =
* Fixed bug with failing nonce check upon single item deletion
* Introduced 'fu_manage_permissions' filter to alter default permissions for managing UGC

= 0.5.7 (July 5th, 2013) =
* Determine if post type of uploaded post is allowed in the plugin's settings rather than than in all registered post types
* If uploadeded post has author set and it's one of the registered users of the blog, post_author is set to that user, otherwise saved as meta
* Set success value to true if no files were uploaded but post was uplaoded succesfully
* Add nested shortcodes after default fields, instead of replacing them

= 0.5.6 (June 26, 2013) =
* Prevent plugin activation if WP is older than 3.3

= 0.5.5 (June 5, 2013 ) =
* Added German translation

= 0.5.4 (May 19, 2013) =
* Fixed bugs with form layouts
* Better readme and FAQ section

= 0.5.3 (Apr 17, 2013) =

* Fixed potential fatal error *

= 0.5.1 (Apr 11, 2013) =

* Ability to autoapprove files( See settings )
* Bugfix: ensure that there's no PHP errors in some certain cases

= 0.5 (Apr 10, 2013) =

* Ability to pick files allowed for uploading from the plugin's settings
* Bugfix: admins won't get any notifications on unsuccessful upload any more

= 0.4.2 (Apr 3, 2013) =

* Minor updates
* Better readme on how to allow various media files

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
