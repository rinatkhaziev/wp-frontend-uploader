<?php
/*
Plugin Name: Frontend Uploader
Description: Allow your visitors to upload content and moderate it.
Author: Rinat Khaziev, Daniel Bachhuber, Ricardo Zappala
Version: 0.5.3
Author URI: http://digitallyconscious.com

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

// Define consts and bootstrap and dependencies
define( 'FU_VERSION', '0.5.3' );
define( 'FU_ROOT' , dirname( __FILE__ ) );
define( 'FU_FILE_PATH' , FU_ROOT . '/' . basename( __FILE__ ) );
define( 'FU_URL' , plugins_url( '/', __FILE__ ) );

require_once FU_ROOT . '/lib/php/class-frontend-uploader-wp-media-list-table.php';
require_once FU_ROOT . '/lib/php/class-frontend-uploader-wp-posts-list-table.php';
require_once FU_ROOT . '/lib/php/class-html-helper.php';
require_once FU_ROOT . '/lib/php/settings-api/class.settings-api.php';
require_once FU_ROOT . '/lib/php/functions.php';
require_once FU_ROOT . '/lib/php/frontend-uploader-settings.php';

class Frontend_Uploader {

	public $allowed_mime_types;
	public $html;
	public $settings;
	public $settings_slug = 'frontend_uploader_settings';
	public $is_debug = false;

	/**
	 * Here we go
	 *
	 * Instantiating the plugin, adding actions, filters, and shortcodes
	 */
	function __construct() {
		// Hooking to wp_ajax
		// @todo refactor in 0.6
		add_action( 'wp_ajax_upload_ugphoto', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_nopriv_upload_ugphoto', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_approve_ugc', array( $this, 'approve_photo' ) );

		add_action( 'wp_ajax_upload_ugc', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_nopriv_upload_ugc', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_approve_ugc_post', array( $this, 'approve_post' ) );

		// Adding media submenu
		add_action( 'admin_menu', array( $this, 'add_menu_items' ) );

		// Currently supported shortcodes
		add_shortcode( 'fu-upload-form', array( $this, 'upload_form' ) );
		add_shortcode( 'input', array( $this, 'shortcode_content_parser' ) );
		add_shortcode( 'textarea', array( $this, 'shortcode_content_parser' ) );

		// Static assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Unautop the shortcode
		add_filter( 'the_content', 'shortcode_unautop', 100 );
		// Hiding not approved attachments from Media Gallery
		// @since core 3.5-beta-1
		add_filter( 'posts_where', array( $this, 'filter_posts_where' ) );

		// Init
		add_action( 'init', array( $this, 'action_init' ) );

		// HTML helper to render HTML elements
		$this->html = new Html_Helper;

		$this->is_debug = (bool) apply_filters( 'fu_is_debug', defined( 'WP_DEBUG' ) && WP_DEBUG );
		// Either use default settings if no setting set, or try to merge defaults with existing settings
		// Needed if new options were added in upgraded version of the plugin
		$this->settings = array_merge( $this->settings_defaults(), (array) get_option( $this->settings_slug, $this->settings_defaults() ) );
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
	}

	/**
	 *  Load languages and a bit of paranoia
	 */
	function action_init() {
		load_plugin_textdomain( 'frontend-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$this->allowed_mime_types = $this->_get_mime_types();
		add_filter( 'upload_mimes', array( $this, '_get_mime_types' ), 999 );
	}

	function _get_mime_types() {
		// Grab default mime-types
		$mime_types = wp_get_mime_types();
		$fu_mime_types = fu_get_mime_types();
		// Workaround for IE
		$mime_types['jpg|jpe|jpeg|pjpg'] = 'image/pjpeg';
		$mime_types['png|xpng'] = 'image/x-png';
		// Iterate through default extensions
		foreach( $fu_mime_types as $extension => $details ) {
			// Skip if it's not in the settings
			if ( !in_array( $extension, $this->settings['enabled_files'] ) )
				continue;

			// Iterate through mime-types for this extension
			foreach( $details['mimes'] as $ext_mime ) {

				$mime_types[ $extension . '|' . $extension . sanitize_title_with_dashes( $ext_mime ) ] = $ext_mime;
			}
		}
		// Configuration filter: fu_allowed_mime_types should return array of allowed mime types (see readme)
		$mime_types = apply_filters( 'fu_allowed_mime_types', $mime_types );

		foreach( $mime_types as $ext_key => $mime ) {
			// Check for php just in case
			if ( false !== strpos( $mime, 'php') )
				unset( $mime_types[$ext_key] );
		}

		return $mime_types;
	}

	/**
	 * Ensure we're not producing any notices by supplying the defaults to get_option
	 *
	 * @return array $defaults
	 */
	function settings_defaults() {
		$defaults = array();
		$settings = Frontend_Uploader_Settings::get_settings_fields();
		foreach ( $settings[$this->settings_slug] as $setting ) {
			$defaults[ $setting['name'] ] = $setting['default'];
		}
		return $defaults;
	}

	function activate_plugin() {
		$defaults = $this->settings_defaults();
		$existing_settings = (array) get_option( $this->settings_slug, $this->settings_defaults() );
		update_option( $this->settings_slug, array_merge( $defaults, (array) $existing_settings ) );
	}

	/**
	 * Since WP 3.5-beta-1 WP Media interface shows private attachments as well
	 * We don't want that, so we force WHERE statement to post_status = 'inherit'
	 *
	 * @since  0.3
	 *
	 * @param string  $where WHERE statement
	 * @return string WHERE statement
	 */
	function filter_posts_where( $where ) {
		if ( !is_admin() )
			return $where;

		$screen = get_current_screen();
		if ( ! defined( 'DOING_AJAX' ) && $screen->base == 'upload' && ( !isset( $_GET['page'] ) || $_GET['page'] != 'manage_frontend_uploader' ) ) {
			$where = str_replace( "post_status = 'private'", "post_status = 'inherit'", $where );
		}
		return $where;
	}

	/**
	 * Determine if we should autoapprove the submission or not
	 * @return boolean [description]
	 */
	function _is_public() {
		return  ( current_user_can( 'read' ) && 'on' == $this->settings['auto_approve_user_files'] ) ||  ( 'on' == $this->settings['auto_approve_any_files'] );
	}

	/**
	 * Handle uploading of the files
	 *
	 * @since  0.4
	 *
	 * @uses media_handle_sideload Don't even know why
	 *
	 * @param int     $post_id Parent post id
	 * @return array Combined result of media ids and errors if any
	 */
	function _handle_files( $post_id ) {
		$media_ids = $errors = array();
		// Bail if there are no files
		if ( empty( $_FILES ) )
			return false;

		// File field name could be user defined, so we just get the first file
		$files = current( $_FILES );

		for ( $i = 0; $i < count( $_FILES['photo']['name'] ); $i++ ) {
			$fields = array( 'name', 'type', 'tmp_name', 'error', 'size' );
			foreach ( $fields as $field ) {
				$k[$field] = $files[$field][$i];
			}

			// Skip to the next file if upload went wrong
			if ( $k['tmp_name'] == "" ) {
				$errors['fu-upload-error'][] = $k['name'];
				continue;
			}

			preg_match( '/.(?P<ext>[a-zA-Z0-9]+)$/', $k['name'], $ext_match );
			// Add an error message if MIME-type is not allowed
			if ( ! in_array( $k['type'], (array) $this->allowed_mime_types ) ) {
					$errors['fu-disallowed-mime-type'][] = array( 'name' => $k['name'], 'mime' => $k['type'] );
				continue;
			}

			// Setup some default values
			// However, you can make additional changes on 'fu_after_upload' action
			$caption = '';

			// Try to set post caption if the field is set on request
			// Fallback to post_content if the field is not set
			// @todo remove this in v0.5 when automatic handling of shortcode attributes is implemented
			if ( isset( $_POST['caption'] ) )
				$caption = sanitize_text_field( $_POST['caption'] );
			elseif ( isset( $_POST['post_content'] ) )
				$caption = sanitize_text_field( $_POST['post_content'] );
			// @todo remove or refactor
			$post_overrides = array(
				'post_status' => $this->_is_public() ? 'publish' : 'private',
				'post_title' => isset( $_POST['post_title'] ) && ! empty( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : 'Unnamed',
				'post_content' => empty( $caption ) ? __( 'Unnamed', 'frontend-uploader' ) : $caption,
				'post_excerpt' => empty( $caption ) ? __( 'Unnamed', 'frontend-uploader' ) :  $caption,
			);

			// Trying to upload the file
			$upload_id = media_handle_sideload( $k, (int) $post_id, $post_overrides['post_title'], $post_overrides );
			if ( !is_wp_error( $upload_id ) )
				$media_ids[] = $upload_id;
			else
				$errors['fu-error-media'][] = $k['name'];
		}

		$success = empty( $errors ) && !empty( $media_ids ) ? true : false;
		// Allow additional setup
		// Pass array of attachment ids
		do_action( 'fu_after_upload', $media_ids, $success );
		return array( 'success' => $success, 'media_ids' => $media_ids, 'errors' => $errors );
	}

	/**
	 * Handle post uploads
	 *
	 * @since 0.4
	 */
	function _upload_post() {
		$errors = array();
		$success = true;
		$post_array = array(
			'post_type' =>  isset( $_POST['post_type'] ) && in_array( $_POST['post_type'], get_post_types() ) ? $_POST['post_type'] : 'post',
			'post_title'    => sanitize_text_field( $_POST['post_title'] ),
			'post_content'  => wp_filter_post_kses( $_POST['post_content'] ),
			'post_status'   => $this->_is_public() ? 'publish' : 'private',
		);

		// Determine if we have a whitelisted category
		$allowed_categories = array_filter( explode( ",", str_replace( " ", "",  $this->settings['allowed_categories'] ) ) );

		if (  isset( $_POST['post_category'] ) && in_array( $_POST['post_category'], $allowed_categories ) ) {
			$post_array = array_merge( $post_array, array( 'post_category' => array( (int) $_POST['post_category'] ) ) );
		}

		$post_id = wp_insert_post( $post_array, true );
		// Something went wrong
		if ( is_wp_error( $post_id ) ) {
			$errors[] = 'fu-error-post';
			$success = false;
		} else {
			do_action( 'fu_after_create_post', $post_id );
			// Save the author name if it was filled and post was created successfully
			$author = isset( $_POST['post_author'] ) ? sanitize_text_field( $_POST['post_author'] ) : '';
			if ( $author )
				add_post_meta( $post_id, 'author_name', $author );
		}

		return array( 'success' => $success, 'post_id' => $post_id, 'errors' => $errors );
	}

	/**
	 * Handle post, post+media, or just media files
	 *
	 * @since  0.4
	 */
	function upload_content() {
		// Bail if something fishy is going on
		if ( !wp_verify_nonce( $_POST['nonceugphoto'], 'upload_ugphoto' ) ) {
			wp_safe_redirect( add_query_arg( array( 'response' => 'fu-error', 'errors' =>  'nonce-failure' ), wp_get_referer() ) );
			exit;
		}
		$layout = isset( $_POST['form_layout'] ) && !empty( $_POST['form_layout'] ) ? $_POST['form_layout'] : 'image';
		switch ( $layout ) {
		case 'post':
			$result = $this->_upload_post();
			break;
		case 'post_image':
		case 'post_media';
			$response = $this->_upload_post();
			if ( ! is_wp_error( $response['post_id'] ) ) {
				$result = $this->_handle_files( $response['post_id'] );
				$result = array_merge( $response, $result );
			}
			break;
		case 'image':
		case 'media':
			if ( isset( $_POST['post_ID'] ) && 0 !== $pid = (int) $_POST['post_ID'] ) {
				$result = $this->_handle_files( $pid );
			}
			break;
		}
		$this->_notify_admin( $result );
		$this->_handle_result( $result );
		exit;
	}

	/**
	 * Notify site administrator by email
	 */
	function _notify_admin( $result = array() ) {
		// Notify site admins of new upload
		if ( ! ( 'on' == $this->settings['notify_admin'] && $result['success'] ) )
			return;
		// @todo It'd be nice to add the list of upload files
		$to = !empty( $this->settings['notification_email'] ) && filter_var( $this->settings['notification_email'], FILTER_VALIDATE_EMAIL ) ? $this->settings['notification_email'] : get_option( 'admin_email' );
		$subj = __( 'New content was uploaded on your site', 'frontend-uploader' );
		wp_mail( $to, $subj, $this->settings['admin_notification_text'] );

	}

	/**
	 * Process response from upload logic
	 *
	 * @since  0.4
	 */
	function _handle_result( $result = array() ) {
		// Redirect to referrer if repsonse is malformed
		if ( empty( $result ) || !is_array( $result ) ) {
			wp_safe_redirect( wp_get_referer() );
			return;
		}

		$errors_formatted = array();
		// Either redirect to success page if it's set and valid
		// Or to referrer
		$url = isset( $_POST['success_page'] ) && filter_var( $_POST['success_page'], FILTER_VALIDATE_URL ) ? $_POST['success_page'] :  strtok( wp_get_referer(), '?' );

		// $query_args will hold everything that's needed for displaying notices to user
		$query_args = array();

		// Set the result to success
		if ( ( isset( $result['success'] ) && $result['success'] ) || 0 < count( $result['media_ids'] ) )
			$query_args['response'] = 'fu-sent';

		// Some errors happened
		// Format a string to be passed as GET value
		if ( !empty( $result['errors'] ) ) {
			$query_args['response'] = 'fu-error';
			$_errors = array();

			// Iterate through key=>value pairs of errors
			foreach ( $result['errors'] as $key => $error ) {
				// Do not display mime-types in production
				if ( !$this->is_debug && isset( $error[0]['mime'] ) )
					unset( $error[0]['mime'] );

				$_errors[$key] = join( ',,,', $error[0] );
			}

			foreach ( $_errors as $key => $value ) {
				$errors_formatted[] = "{$key}:{$value}";
			}

			$query_args['errors'] = join( ';', $errors_formatted );
		}

		wp_safe_redirect( add_query_arg( array( $query_args ) , $url ) );
	}

	/**
	 * Render various admin template files
	 *
	 * @param string  $view file slug
	 * @since 0.4
	 */
	function render( $view = '' ) {
		if ( empty( $view ) )
			return;

		$file = FU_ROOT . "/lib/views/{$view}.tpl.php";
		if ( file_exists( $file ) )
			require $file;
	}

	/**
	 * Display media list table
	 * @return [type] [description]
	 */
	function admin_list() {
		$this->render( 'manage-ugc-media' );
	}

	/**
	 * Display posts/custom post types table
	 * @return [type] [description]
	 */
	function admin_posts_list() {
		$this->render( 'manage-ugc-posts' );
	}

	/**
	 * Add submenu items
	 */
	function add_menu_items() {
		add_media_page( __( 'Manage UGC', 'frontend-uploader' ), __( 'Manage UGC', 'frontend-uploader' ), 'edit_posts', 'manage_frontend_uploader', array( $this, 'admin_list' ) );
		foreach ( (array) $this->settings['enabled_post_types'] as $cpt ) {
			if ( $cpt == 'post' ) {
				add_posts_page( __( 'Manage UGC Posts', 'frontend-uploader' ), __( 'Manage UGC', 'frontend-uploader' ), 'edit_posts', 'manage_frontend_posts_uploader', array( $this, 'admin_posts_list' ) );
				continue;
			}

			add_submenu_page( "edit.php?post_type={$cpt}", __( 'Manage UGC Posts', 'frontend-uploader' ), __( 'Manage UGC', 'frontend-uploader' ), 'edit_posts', "manage_frontend_{$cpt}s_uploader", array( $this, 'admin_posts_list' ) );
		}
	}

	/**
	 * Approve a media file
	 *
	 * @todo refactor in 0.6
	 * @return [type] [description]
	 */
	function approve_photo() {
		// Check permissions, attachment ID, and nonce
		if ( !current_user_can( 'edit_posts' ) || intval( $_GET['id'] ) == 0 || !wp_verify_nonce( $_GET['nonceugphoto'], 'upload_ugphoto' ) )
			wp_safe_redirect( get_admin_url( null, 'upload.php?page=manage_frontend_uploader&error=id_or_perm' ) );

		$post = get_post( $_GET['id'] );

		if ( is_object( $post ) && $post->post_status == 'private' ) {
			$post->post_status = 'inherit';
			wp_update_post( $post );
			$this->update_35_gallery_shortcode( $post->post_parent, $post->ID );
			wp_safe_redirect( get_admin_url( null, 'upload.php?page=manage_frontend_uploader&approved=1' ) );
		}

		wp_safe_redirect( get_admin_url( null, 'upload.php?page=manage_frontend_uploader' ) );
		exit;
	}

	/**
	 *
	 *
	 * @todo refactor in 0.6
	 * @return [type] [description]
	 */
	function approve_post() {
		// check for permissions and id
		$url = get_admin_url( null, 'edit.php?page=manage_frontend_posts_uploader&error=id_or_perm' );
		if ( !current_user_can( 'edit_posts' ) || intval( $_GET['id'] ) == 0  )
			wp_safe_redirect( $url );

		$post = get_post( $_GET['id'] );

		$images = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post->ID );

		foreach ( $images as $imageID => $imagePost ) {
			$current_image = array();
			$current_image['ID'] = $imageID;
			$current_image['post_status'] = "publish";
			wp_update_post( $current_image );
		}
		if ( !is_wp_error( $post ) ) {
			$post->post_status = 'publish';
			wp_update_post( $post );
			$post_type = $post->post_type == 'post' ? array() : array( 'post_type' => $post->post_type );
			$url = add_query_arg(
				array_merge( array(
						'page' => "manage_frontend_{$post->post_type}s_uploader",
						'approved' => 1,
					), $post_type ), get_admin_url( null, "edit.php" ) );

		}

		wp_safe_redirect( $url );
		exit;
	}


	/**
	 * Shortcode callback for inner content of [fu-upload-form] shortcode
	 *
	 * @param array   $atts    shortcode attributes
	 * @param unknown $content not used
	 * @param string  $tag
	 */
	function shortcode_content_parser( $atts, $content = null, $tag ) {
		$atts = shortcode_atts( array(
					'id' => '',
					'name' => '',
					'description' => '',
					'value' => '',
					'type' => '',
					'class' => '',
					'multiple' => 'false',
					'wysiwyg_enabled' => false,
				), $atts );
		$callback = array( $this, "_render_{$tag}" );
		if ( is_callable( $callback ) )
			return call_user_func( $callback, $atts );
	}

	function _render_input( $atts ) {
		extract( $atts );
		$atts = array( 'id' => $id, 'class' => $class, 'multiple' => $multiple );
		// Workaround for HTML5 multiple attribute
		if ( $multiple == 'false' )
			unset( $atts['multiple'] );

		// Allow multiple file upload by default.
		// To do so, we need to add array notation to name field: []
		if ( !strpos( $name, '[]' ) && $type == 'file' )
			$name = $name . '[]';

		$element = $this->html->element( 'label', $description . $this->html->input( $type, $name, $value, $atts ) , array( 'for' => $id ), false );

		return $this->html->element( 'div', $element, array( 'class' => 'ugc-input-wrapper' ), false );
	}

	function _render_textarea( $atts ) {
		extract( $atts );
		// Render WYSIWYG textara
		if ( ( isset( $this->settings['wysiwyg_enabled'] ) && 'on' == $this->settings['wysiwyg_enabled'] ) || $wysiwyg_enabled == true ) {
			ob_start();
			wp_editor( '', $id, array(
					'textarea_name' => $name,
					'media_buttons' => false,
					'teeny' => true,
					'quicktags' => false
				) );
			$tiny = ob_get_clean();
			$label =  $this->html->element( 'label', $description , array( 'for' => $id ), false );
			return $this->html->element( 'div', $label . $tiny, array( 'class' => 'ugc-input-wrapper' ), false ) ;
		}
		// Render plain textarea
		$element = $this->html->element( 'label', $description . $this->html->element( 'textarea', '', array(
			'name' => $name,
			'id' => $id,
			'class' => $class
		) ), array( 'for' => $id ), false );

		return $this->html->element( 'div', $element, array( 'class' => 'ugc-input-wrapper' ), false );
	}

	function _render_checkboxes( $atts ) {
		extract( $atts );
		return;
	}

	function _render_radio( $atts ) {
		extract( $atts );
		return;
	}

	function _render_select( $atts ) {
		extract( $atts );
		return;
	}

	/**
	 * Display the upload post form
	 *
	 * @todo Major refactoring for this before releasing 0.5
	 *
	 * @param array   $atts    shortcode attributes
	 * @param string  $content content that is encloded in [fu-upload-form][/fu-upload-form]
	 */
	function upload_form( $atts, $content = null ) {

		// Reset postdata in case it got polluted somewhere
		wp_reset_postdata();
		extract( shortcode_atts( array(
					'description' => '',
					'title' => __( 'Submit a new post', 'frontend-uploader' ),
					'type' => '',
					'class' => 'validate',
					'category' => '1',
					'success_page' => '',
					'form_layout' => '',
					'post_id' => get_the_ID(),
					'post_type' => 'post',
				), $atts ) );
		$post_id = (int) $post_id;

		if ( $form_layout != "post" && $form_layout != "post_image" )
			$form_layout = "image";

		if ( $form_layout == 'image' )
			$title = __( 'Submit a media file', 'frontend-uploader' );

		ob_start();
?>
	<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post" id="ugc-media-form" class="<?php echo esc_attr( $class )?>" enctype="multipart/form-data">
	  <div class="ugc-inner-wrapper">
		  <h2><?php echo esc_html( $title ) ?></h2>
<?php
		if ( !empty( $_GET ) )
			$this->_display_response_notices( $_GET );

		// Parse nested shortcodes
		if ( $content ) {
			echo do_shortcode( $content );
		// Or render default form
		} else {
			$textarea_desc = __( 'Description', 'frontend-uploader' );
			$file_desc = __( 'Your Photo', 'frontend-uploader' );
			$submit_button = __( 'Submit', 'frontend-uploader' );

			echo do_shortcode ( '[input type="text" name="post_title" id="ug_post_title" description="' . __( 'Title', 'frontend-uploader' ) . '" class="required"]' );

			// here we select the different fields based on the form layout to allow for different types
			// of uploads (only a file, only a post or a file and post)

			// @todo refactor
			if ( $form_layout == "post_image" )
				echo do_shortcode( '[textarea name="post_content" class="textarea" id="ug_content" class="required" description="'. $textarea_desc .'"]
								    [input type="file" name="photo" id="ug_photo" description="'. $file_desc .'" multiple=""]' );
			elseif ( $form_layout == "post" )
				echo do_shortcode( '[textarea name="post_content" class="textarea" id="ug_content" class="required" description="'. $textarea_desc .'"]' );
			else
				echo do_shortcode( '[textarea name="caption" class="textarea tinymce-enabled" id="ugcaption" description="'. $textarea_desc .'"]
										[input type="file" name="photo" id="ug_photo" class="required" description="'. $file_desc .'" multiple=""]' );

			if ( isset( $this->settings['show_author'] )  && $this->settings['show_author'] )
				echo do_shortcode ( '[input type="text" name="post_author" id="ug_post_author" description="' . __( 'Author', 'frontend-uploader' ) . '" class=""]' );

			if ( $form_layout == "post_image" || $form_layout == "image" )
				echo do_shortcode ( '[input type="text" name="post_credit" id="ug_post_credit" description="' . __( 'Credit', 'frontend-uploader' ) . '" class=""]' );

			echo do_shortcode ( '[input type="submit" class="btn" value="'. $submit_button .'"]' );
			}
			?>
		  <input type="hidden" name="action" value="upload_ugc" />
		  <input type="hidden" value="<?php echo $post_id ?>" name="post_ID" />
		  <input type="hidden" value="<?php echo $category; ?>" name="post_category" />
		  <input type="hidden" value="<?php echo $success_page; ?>" name="success_page" />
		  <input type="hidden" value="<?php echo $form_layout; ?>" name="form_layout" />

		  <?php
		if ( in_array( $form_layout, array( "post_image", "post" ) ) ): ?>
		  <input type="hidden" value="<?php echo $post_type; ?>" name="post_type" />
		<?php endif;
		// Allow a little customization
		do_action( 'fu_additional_html' );
?>
		  <?php wp_nonce_field( 'upload_ugphoto', 'nonceugphoto' ); ?>
		  <div class="clear"></div>
	  </div>
	  </form>
<?php
		return ob_get_clean();
	}

	/**
	 * Returns html chunk of single notice
	 *
	 * @since 0.4
	 *
	 * @param string  $message Text of the message
	 * @param string  $class   Class of container
	 * @return string          [description]
	 */
	function _notice_html( $message, $class ) {
		if ( empty( $message ) || empty( $class ) )
			return;
		return sprintf( '<p class="ugc-notice %1$s">%2$s</p>', $class, $message );
	}

	/**
	 * Handle response notices
	 *
	 * @since 0.4
	 *
	 * @param array   $res [description]
	 * @return [type]      [description]
	 */
	function _display_response_notices( $res = array() ) {
		if ( empty( $res ) )
			return;

		$output = '';
		$map = array(
			'fu-sent' => array(
				'text' => __( 'Your file was successfully uploaded!', 'frontend-uploader' ),
				'class' => 'success',
			),
			'fu-error' => array(
				'text' => __( 'There was an error with your submission', 'frontend-uploader' ),
				'class' => 'failure',
			),
		);

		if ( isset( $res['response'] ) && isset( $map[ $res['response'] ] ) )
			$output .= $this->_notice_html( $map[ $res['response'] ]['text'] , $map[ $res['response'] ]['class'] );

		if ( !empty( $res['errors' ] ) )
			$output .= $this->_display_errors( $res['errors' ] );

		echo $output;
	}
	/**
	 * Handle errors
	 *
	 * @since 0.4
	 * @param string  $errors [description]
	 * @return string HTML
	 */
	function _display_errors( $errors ) {
		$errors_arr = explode( ';', $errors );
		$output = '';
		$map = array(
			'nonce-failure' => array(
				'text' => __( 'Security check failed!', 'frontend-uploader' ),
			),
			'fu-disallowed-mime-type' => array(
				'text' => __( 'This kind of file is not allowed. Please, try again selecting other file.', 'frontend-uploader' ),
				'format' => $this->is_debug ? '%1$s: <br/> File name: %2$s <br/> MIME-TYPE: %3$s' : '%1$s: <br/> %2$s',
			),
			'fu-invalid-post' => array(
				'text' =>__( 'The content you are trying to post is invalid.', 'frontend-uploader' ),
			)
		);

		foreach ( $errors_arr as $error ) {
			$error_type = explode( ':', $error );
			$error_details = explode( '|', $error_type[1] );
			// Iterate over different errors
			foreach ( $error_details as $single_error ) {

				// And see if there's any additional details
				$details = isset( $single_error ) ? explode( ',,,', $single_error ) : explode( ',,,', $single_error );
				// Add a description to our details array
				array_unshift( $details, $map[ $error_type[0] ]['text']  );
				// If we have a format, let's format an error
				// If not, just display the message
				if ( isset( $map[ $error_type[0] ]['format'] ) )
					$message = vsprintf( $map[ $error_type[0] ]['format'], $details );
				else
					$message = $map[ $error_type[0] ]['text'];
			}
			$output .= $this->_notice_html( $message, 'failure' );
		}

		return $output;
	}

	/**
	 * Enqueue our assets
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'frontend-uploader', FU_URL . 'lib/css/frontend-uploader.css' );
		wp_enqueue_script( 'jquery-validate', FU_URL .' lib/js/validate/jquery.validate.js ', array( 'jquery' ) );
		wp_enqueue_script( 'frontend-uploader-js', FU_URL . 'lib/js/frontend-uploader.js', array( 'jquery', 'jquery-validate' ) );
		// Include localization strings for default messages of validation plugin
		$wplang = apply_filters( 'fu_wplang', WPLANG );
		if ( $wplang ) {
			$lang = explode( '_', $wplang );
			$url = FU_URL . "lib/js/validate/localization/messages_{$lang[0]}.js";
			wp_enqueue_script( 'jquery-validate-messages', $url, array( 'jquery' ) );
		}

	}

	/**
	 * Enqueue scripts for admin
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_script( 'wp-ajax-response' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'media' );
	}

	/**
	 * 3.5 brings new Media UI
	 * Unfortunately, we have to specify ids of approved attachments explicitly,
	 * Otherwise, editors have to pick photos after they have already approved them in "Manage UGC"
	 *
	 * This method will search a parent post with a regular expression, and update gallery shortcode with freshly approved attachment ID
	 *
	 * @return post id/wp_error
	 */
	function update_35_gallery_shortcode( $post_id, $attachment_id ) {
		global $wp_version;

		if ( version_compare( $wp_version, '3.5', '>=' )  && (int) $post_id != 0 ) {
			$parent = get_post( $post_id );
			preg_match( '#(?<before>(.*))\[gallery(.*)ids=(\'|")(?<ids>[0-9,]*)(\'|")](?<after>(.*))#ims', $parent->post_content, $matches ) ;
			if ( isset( $matches['ids'] ) ) {
				// @todo account for other possible shortcode atts
				$gallery = '[gallery ids="' . $matches['ids'] . ',' . (int) $attachment_id .'"]';
				$post_to_update = array(
					'ID' => (int) $post_id,
					'post_content' => $matches['before'] . $gallery . $matches['after']
				);
				return wp_update_post( $post_to_update );
			}

		}
		return;
	}

}

$frontend_uploader = new Frontend_Uploader;