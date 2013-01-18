<?php
/*
Plugin Name: UGC Frontend Uploader
Description: Allow your visitors to upload content and moderate it.
Author: Rinat Khaziev
Version: 0.4-working
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

// Define our paths and urls and bootstrap
define( 'UGC_VERSION', '0.4-working' );
define( 'UGC_ROOT' , dirname( __FILE__ ) );
define( 'UGC_FILE_PATH' , UGC_ROOT . '/' . basename( __FILE__ ) );
define( 'UGC_URL' , plugins_url( '/', __FILE__ ) );

require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once UGC_ROOT . '/lib/php/class-frontend-uploader-wp-media-list-table.php';
require_once UGC_ROOT . '/lib/php/class-frontend-uploader-wp-posts-list-table.php';
require_once UGC_ROOT . '/lib/php/class-html-helper.php';
require_once UGC_ROOT . '/lib/php/settings-api/class.settings-api.php';
require_once UGC_ROOT . '/lib/php/frontend-uploader-settings.php';

class Frontend_Uploader {

	public $allowed_mime_types;
	public $html;
	public $settings;
	public $ugc_mimes;

	/**
	 *  Load languages and a bit of paranoia
	 */
	function action_init() {
		load_plugin_textdomain( 'frontend-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		$this->allowed_mime_types = $this->mime_types();

		// Disallow php files no matter what (this is a full list of possible mime types for php scripts)
		// @todo may be add other executables
		// WP allows any mime-type that's specified within upload_mimes filter
		// I strongly believe in fail-safe devices
		// So lets just don't take any chances with php files (at least)
		$no_pasaran = array( 'application/x-php', 'text/x-php', 'text/php', 'application/php', 'application/x-httpd-php', 'application/x-httpd-php-source' );
		// THEY SHALL NOT PASS
		foreach ( $no_pasaran as $np ) {
			if ( false !== ( $key = array_search( $np, $this->allowed_mime_types ) ) ) {
				unset( $this->allowed_mime_types[$key] );
			}
		}
	}

	/**
	 * Workaround for allowed mime-types
	 *
	 * @return allowed mime-types
	 */
	function mime_types() {
		$this->ugc_mimes = apply_filters( 'fu_allowed_mime_types', $this->fix_ie_mime_types( wp_get_mime_types() ) );
		add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes' ) );
		return $this->ugc_mimes;
	}

	function filter_upload_mimes( $mimes ) {
		return $this->ugc_mimes;
	}

	/**
	 * Add IE-specific MIME types
	 * /props mcnasby
	 *
	 * @param array   $mime_types [description]
	 * @return [type] [description]
	 */
	function fix_ie_mime_types( $mime_types ) {
		$mime_types['jpg|jpe|jpeg'] = 'image/pjpeg';
		$mime_types['png|pngg'] = 'image/x-png';
		return $mime_types;
	}

	/**
	 * Here we go
	 */
	function __construct() {
		// Hooking to wp_ajax
		add_action( 'wp_ajax_upload_ugphoto', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_nopriv_upload_ugphoto', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_approve_ugc', array( $this, 'approve_photo' ) );

		add_action( 'wp_ajax_upload_ugpost', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_nopriv_upload_ugpost', array( $this, 'upload_content' ) );
		add_action( 'wp_ajax_approve_ugc_post', array( $this, 'approve_post' ) );

		// Adding media submenu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Adding the posts submenu
		add_action( 'admin_menu', array( $this, 'add_posts_menu_item' ) );

		// Adding our shortcodes
		add_shortcode( 'fu-upload-form', array( $this, 'upload_form' ) );
		add_shortcode( 'input', array( $this, 'shortcode_content_parser' ) );
		add_shortcode( 'textarea', array( $this, 'shortcode_content_parser' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Preventing wpautop going crazy on
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'wpautop' , 99 );
		add_filter( 'the_content', 'shortcode_unautop', 100 );
		// Hiding not approved attachments from Media Gallery
		// @since core 3.5-beta-1
		add_filter( 'posts_where', array( $this, 'filter_posts_where' ) );

		// Localization
		add_action( 'init', array( $this, 'action_init' ) );
		// Configuration filter:
		// fu_allowed_mime_types should return array of allowed mime types
		// HTML helper to render HTML elements
		$this->html = new Html_Helper;
		$this->settings = get_option( 'frontend_uploader_settings', $this->settings_defaults() );

	}

	/**
	 * Ensure we're not producing any notices by supplying the defaults to get_option
	 *
	 * @return array $defaults
	 */
	function settings_defaults() {
		$defaults = array();
		$settings = Frontend_Uploader_Settings::get_settings_fields();
		foreach ( $settings['frontend_uploader_settings'] as $setting ) {
			$defaults[ $setting['name'] ] = $setting['default'];
		}
		return $defaults;
	}

	/**
	 * Since WP 3.5-beta-1 WP Media interface shows private attachments as well
	 * We don't want that, so we force WHERE statement to post_status = 'inherit'
	 *
	 * @todo  probably intermediate workaround
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
	 * Handles the upload of a user's photo
	 */
	function upload_photo() {
		$media_ids = array(); // will hold uploaded media IDS

		if ( !wp_verify_nonce( $_POST['nonceugphoto'], 'upload_ugphoto' ) ) {
			wp_safe_redirect( add_query_arg( array( 'response' => 'nonce-failure' ), $_POST['_wp_http_referer'] ) );
			exit;
		} // If nonce is invalid, redirect to referer and display error flash notice

		if ( !empty( $_FILES ) && intval( $_POST['post_ID'] ) != 0 ) {
			// File field name could be user defined, so we just pick
			$files = current( $_FILES );
			for ( $i = 0; $i < count( $_FILES['photo']['name'] ); $i++ ) {
				$fields = array( 'name', 'type', 'tmp_name', 'error', 'size' );
				foreach ( $fields as $field ) {
					$k[$field] = $files[$field][$i];
				}
				// Iterate through files, and save upload if it's one of allowed MIME types
				if ( in_array( $k['type'], $this->allowed_mime_types ) ) {
					// Setup some default values
					// However, you can make additional changes on 'fu_after_upload' action
					$post_overrides = array(
						'post_status' => 'private',
						'post_title' => isset( $_POST['caption'] ) && ! empty( $_POST['caption'] ) ? filter_var( $_POST['caption'], FILTER_SANITIZE_STRING ) : 'Unnamed',
						'post_content' => !empty( $_POST['name'] ) ? __( 'Courtesy of ', 'frontend-uploader' ) . filter_var( $_POST['name'], FILTER_SANITIZE_STRING ) : '',
					);
					$media_ids[] =  media_handle_sideload( $k, intval( $_POST['post_ID'] ), $post_overrides['post_title'], $post_overrides );
				} else {
					wp_safe_redirect( add_query_arg( array( 'response' => 'ugc-disallowed_mime_type', 'mime' => $k['type'] ), $_POST['_wp_http_referer'] ) );
					die;
				}
			}
		} else {
			return;
		}

		// @todo check $media_ids for is_wp_error
		// Allow additional setup
		// Pass array of attachment ids
		do_action( 'fu_after_upload', $media_ids );

		// Notify site admins of new upload
		if ( 'on' == $this->settings['notify_admin'] ) {
			$to = !empty( $this->settings['notification_email'] ) && filter_var( $this->settings['notification_email'], FILTER_VALIDATE_EMAIL ) ? $this->settings['notification_email'] : get_option( 'admin_email' );
			$subj = __( 'New file was uploaded on your site', 'frontend-uploader' );
			wp_mail( $to, $subj, $this->settings['admin_notification_text'] );
		}

		if ( $_POST['_wp_http_referer'] )
			wp_safe_redirect( add_query_arg( array( 'response' => 'ugc-sent' ), $_POST['_wp_http_referer'] ) );
		exit;
	}



	/**
	 * Handles the upload of a form, attached image or both
	 */
	function upload_content() {

		if ( $_POST['form_layout']=="post" || $_POST['form_layout']=="post_image" ) {

			// first thing we'll do is create the post
			$page = array(
				'post_type' => 'post',
				'post_title'    => sanitize_text_field( $_POST['post_title'] ),
				'post_content'  => sanitize_text_field( $_POST['post_content'] ),
				'post_status'   => 'private',
				'post_category' => array( intval( sanitize_text_field( $_POST['post_category'] ) ) )
			);

			// if the category is valid create the post
			$allowed_categories = array_filter( explode( ",", str_replace( " ", "",  $this->settings['allowed_categories'] ) ) );

			if ( count( $allowed_categories ) == 0 || in_array( $_POST['post_category'], $allowed_categories ) ) {
				$pageid = wp_insert_post ( $page );
				// now lets add the author's name as a custom field (if it was used/filled and the post is good)
				$author_name = filter_var( $_POST['post_author'], FILTER_SANITIZE_STRING );
				if ( $pageid > 0 && $author_name !="" )
					add_post_meta( $pageid, 'author_name', $author_name );
			}
			else {
				wp_safe_redirect( add_query_arg( array( 'response' => 'invalid_post' ), $_POST['_wp_http_referer'] ) );
				exit;
			}

		} else {
			// well it's only an image, no post so lets set the id to form page's id
			$pageid = intval( $_POST['post_ID'] );
		}


		// now that we know what to relate the image to we can go ahead with the image upload

		// but only if it's the proper form layout
		if ( $_POST['form_layout']=="image" || $_POST['form_layout']=="post_image" ) {

			if ( $_POST['form_layout']=="image" )
				$caption = sanitize_text_field( $_POST['caption'] );
			else
				$caption = sanitize_text_field( $_POST['post_content'] );


			$media_ids = array(); // will hold uploaded media IDS

			if ( !wp_verify_nonce( $_POST['nonceugphoto'], 'upload_ugphoto' ) ) {
				wp_safe_redirect( add_query_arg( array( 'response' => 'nonce-failure' ), $_POST['_wp_http_referer'] ) );
				exit;
			} // If nonce is invalid, redirect to referer and display error flash notice

			if ( !empty( $_FILES ) && intval( $pageid ) != 0 ) {

				// File field name could be user defined, so we just pick
				$files = current( $_FILES );

				for ( $i = 0; $i < count( $_FILES['photo']['name'] ); $i++ ) {
					$fields = array( 'name', 'type', 'tmp_name', 'error', 'size' );
					foreach ( $fields as $field ) {
						$k[$field] = $files[$field][$i];
					}

					// if the tmp file exists then iterate through files, and save
					// upload if it's one of allowed MIME types

					if ( $k['tmp_name'] != "" ) {
						if ( in_array( $k['type'], $this->allowed_mime_types ) ) {
							// Setup some default values
							// However, you can make additional changes on 'fu_after_upload' action
							$post_overrides = array(
								'post_status' => 'private',
								'post_title' => isset( $_POST['post_title'] ) && ! empty( $_POST['post_title'] ) ? filter_var( $_POST['post_title'], FILTER_SANITIZE_STRING ) : 'Unnamed',
								'post_content' => empty( $caption ) ? __( 'Courtesy of ', 'frontend-uploader' ) . filter_var( $_POST['name'], FILTER_SANITIZE_STRING ) : filter_var( $caption, FILTER_SANITIZE_STRING ),
								'post_excerpt' => empty( $caption ) ? __( 'Courtesy of ', 'frontend-uploader' ) . filter_var( $_POST['name'], FILTER_SANITIZE_STRING ) : filter_var( $caption, FILTER_SANITIZE_STRING ),
							);

							$media_ids[] =  media_handle_sideload( $k, intval( $pageid ), $post_overrides['post_title'], $post_overrides );
						} else {
							wp_safe_redirect( add_query_arg( array( 'response' => 'ugc-disallowed_mime_type' ), $_POST['_wp_http_referer'] ) );
							// if the image wasn't allowed then delete the post
							// that was just created
							$post_to_remove['ID'] = $pageid;
							$post_to_remove['post_status'] = 'trash';
							wp_update_post( $post_to_remove );
							die;
						}
					}

				}
			} else {
				return;
			}
			// @todo check $media_ids for is_wp_error
			// Allow additional setup
			// Pass array of attachment ids
			do_action( 'fu_after_upload', $media_ids );

		}


		// Notify site admins of new upload
		if ( 'on' == $this->settings['notify_admin'] ) {
			$to = !empty( $this->settings['notification_email'] ) && filter_var( $this->settings['notification_email'], FILTER_VALIDATE_EMAIL ) ? $this->settings['notification_email'] : get_option( 'admin_email' );
			$subj = __( 'New content was uploaded on your site', 'frontend-uploader' );
			wp_mail( $to, $subj, $this->settings['admin_notification_text'] );
		}

		// and can't believe we got this far, on to the success page (if we have one)

		if ( $_POST['success_page'] ) {
			wp_safe_redirect( $_POST['success_page'] );
			exit;
		} else {
			// and if we don't have one use the traditional method
			if ( $_POST['_wp_http_referer'] )
				wp_safe_redirect( add_query_arg( array( 'response' => 'ugc-sent' ), $_POST['_wp_http_referer'] ) );
			exit;
		}

	}

	function admin_list() {
		$title = __( 'Manage UGC', 'frontend-uploader' );
		set_current_screen( 'upload' );
		if ( ! current_user_can( 'upload_files' ) )
			wp_die( __( 'You do not have permission to upload files.', 'frontend-uploader' ) );

		$wp_list_table = new FU_WP_Media_List_Table();

		$pagenum = $wp_list_table->get_pagenum();
		$doaction = $wp_list_table->current_action();
		$wp_list_table->prepare_items();
		wp_enqueue_script( 'wp-ajax-response' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'media' );
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?> <a href="media-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'file' ); ?></a> <?php
		if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] )
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'frontend-uploader' ) . '</span>', get_search_query() ); ?>
</h2>

<?php
		$message = '';
		if ( isset( $_GET['posted'] ) && (int) $_GET['posted'] ) {
			$message = __( 'Media attachment updated.', 'frontend-uploader' );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'posted' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['attached'] ) && (int) $_GET['attached'] ) {
			$attached = (int) $_GET['attached'];
			$message = sprintf( _n( 'Reattached %d attachment.', 'Reattached %d attachments.', $attached ), $attached );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'attached' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['deleted'] ) && (int) $_GET['deleted'] ) {
			$message = sprintf( _n( 'Media attachment permanently deleted.', '%d media attachments permanently deleted.', $_GET['deleted'] ), number_format_i18n( $_GET['deleted'] ) );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'deleted' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['trashed'] ) && (int) $_GET['trashed'] ) {
			$message = sprintf( _n( 'Media attachment moved to the trash.', '%d media attachments moved to the trash.', $_GET['trashed'] ), number_format_i18n( $_GET['trashed'] ) );
			$message .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), "bulk-media" ) ) . '">' . __( 'Undo', 'frontend-uploader' ) . '</a>';
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'trashed' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['untrashed'] ) && (int) $_GET['untrashed'] ) {
			$message = sprintf( _n( 'Media attachment restored from the trash.', '%d media attachments restored from the trash.', $_GET['untrashed'] ), number_format_i18n( $_GET['untrashed'] ) );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'untrashed' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['approved'] ) ) {
			$message = 'The photo was approved';
		}

		$messages[1] = __( 'Media attachment updated.', 'frontend-uploader' );
		$messages[2] = __( 'Media permanently deleted.', 'frontend-uploader' );
		$messages[3] = __( 'Error saving media attachment.', 'frontend-uploader' );
		$messages[4] = __( 'Media moved to the trash.', 'frontend-uploader' ) . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), "bulk-media" ) ) . '">' . __( 'Undo', 'frontend-uploader' ) . '</a>';
		$messages[5] = __( 'Media restored from the trash.', 'frontend-uploader' );

		if ( isset( $_GET['message'] ) && (int) $_GET['message'] ) {
			$message = $messages[$_GET['message']];
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message' ), $_SERVER['REQUEST_URI'] );
		}

		if ( !empty( $message ) ) { ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php } ?>

<?php $wp_list_table->views(); ?>

<form id="posts-filter" action="" method="get">

<?php $wp_list_table->search_box( __( 'Search Media', 'frontend-uploader' ), 'media' ); ?>

<?php $wp_list_table->display(); ?>

<div id="ajax-response"></div>
<?php find_posts_div(); ?>
<br class="clear" />

</form>
</div>
<?php
	}


	function admin_posts_list() {

		$title = __( 'Manage UGC Posts', 'frontend-uploader' );
		set_current_screen( 'post' );
		if ( ! current_user_can( 'publish_posts' ) )
			wp_die( __( 'You do not have permission to publish posts.', 'frontend-uploader' ) );

		$wp_post_list_table = new FU_WP_Posts_List_Table();



		$pagenum = $wp_post_list_table->get_pagenum();
		$doaction = $wp_post_list_table->current_action();
		$wp_post_list_table->prepare_items();
		wp_enqueue_script( 'wp-ajax-response' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'media' );

?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?> <a href="posts-new.php" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'file' ); ?></a> <?php
		if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] )
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'frontend-uploader' ) . '</span>', get_search_query() ); ?>
</h2>

<?php
		$message = '';
		if ( isset( $_GET['posted'] ) && (int) $_GET['posted'] ) {
			$message = __( 'Media attachment updated.', 'frontend-uploader' );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'posted' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['attached'] ) && (int) $_GET['attached'] ) {
			$attached = (int) $_GET['attached'];
			$message = sprintf( _n( 'Reattached %d attachment.', 'Reattached %d attachments.', $attached ), $attached );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'attached' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['deleted'] ) && (int) $_GET['deleted'] ) {
			$message = sprintf( _n( 'Media attachment permanently deleted.', '%d media attachments permanently deleted.', $_GET['deleted'] ), number_format_i18n( $_GET['deleted'] ) );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'deleted' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['trashed'] ) && (int) $_GET['trashed'] ) {
			$message = sprintf( _n( 'Media attachment moved to the trash.', '%d media attachments moved to the trash.', $_GET['trashed'] ), number_format_i18n( $_GET['trashed'] ) );
			$message .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), "bulk-media" ) ) . '">' . __( 'Undo', 'frontend-uploader' ) . '</a>';
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'trashed' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['untrashed'] ) && (int) $_GET['untrashed'] ) {
			$message = sprintf( _n( 'Media attachment restored from the trash.', '%d media attachments restored from the trash.', $_GET['untrashed'] ), number_format_i18n( $_GET['untrashed'] ) );
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'untrashed' ), $_SERVER['REQUEST_URI'] );
		}

		if ( isset( $_GET['approved'] ) ) {
			$message = 'The post was approved';
		}

		$messages[1] = __( 'Media attachment updated.', 'frontend-uploader' );
		$messages[2] = __( 'Media permanently deleted.', 'frontend-uploader' );
		$messages[3] = __( 'Error saving media attachment.', 'frontend-uploader' );
		$messages[4] = __( 'Media moved to the trash.', 'frontend-uploader' ) . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), "bulk-media" ) ) . '">' . __( 'Undo', 'frontend-uploader' ) . '</a>';
		$messages[5] = __( 'Media restored from the trash.', 'frontend-uploader' );

		if ( isset( $_GET['message'] ) && (int) $_GET['message'] ) {
			$message = $messages[$_GET['message']];
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message' ), $_SERVER['REQUEST_URI'] );
		}

		if ( !empty( $message ) ) { ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php } ?>

<?php $wp_post_list_table->views(); ?>

<form id="posts-filter" action="" method="get">

<?php $wp_post_list_table->search_box( __( 'Search Posts', 'frontend-uploader' ), 'posts' ); ?>

<?php $wp_post_list_table->display(); ?>

<div id="ajax-response"></div>
<?php find_posts_div(); ?>
<br class="clear" />

</form>
</div>
<?php
	}


	function add_menu_item() {
		add_media_page( __( 'Manage UGC', 'frontend-uploader' ), __( 'Manage UGC', 'frontend-uploader' ), 'edit_posts', 'manage_frontend_uploader', array( $this, 'admin_list' ) );
	}

	function add_posts_menu_item() {
		add_posts_page( __( 'Manage UGC Posts', 'frontend-uploader' ), __( 'Manage UGC', 'frontend-uploader' ), 'edit_posts', 'manage_frontend_posts_uploader', array( $this, 'admin_posts_list' ) );

	}

	function approve_photo() {
		// check for permissions and id
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


	function approve_post() {
		// check for permissions and id
		if ( !current_user_can( 'edit_posts' ) || intval( $_GET['id'] ) == 0 || !wp_verify_nonce( 'nonceugphoto', 'upload_ugphoto' ) )
			wp_safe_redirect( get_admin_url( null, 'edit.php?page=manage_frontend_posts_uploader&error=id_or_perm' ) );

		$post = get_post( $_GET['id'] );

		$images =& get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post->ID );


		foreach ( $images as $imageID => $imagePost ) {
			$current_image = array();
			$current_image['ID'] = $imageID;
			$current_image['post_status'] = "publish";
			wp_update_post( $current_image );
		}

		if ( is_object( $post ) && $post->post_status == 'private' ) {

			$post->post_status = 'publish';
			wp_update_post( $post );
			wp_safe_redirect( get_admin_url( null, 'edit.php?page=manage_frontend_posts_uploader&approved=1' ) );
		}

		wp_safe_redirect( get_admin_url( null, 'edit.php?page=manage_frontend_posts_uploader' ) );
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
		extract( shortcode_atts( array(
					'id' => '',
					'name' => '',
					'description' => '',
					'value' => '',
					'type' => '',
					'class' => '',
					'multiple' => 'false',
				), $atts ) );
		switch ( $tag ):
		case 'textarea':
			$element = $this->html->element( 'label', $description . $this->html->element( 'textarea', '', array( 'name' => $name, 'id' => $id, 'class' => $class ) ), array( 'for' => $id ), false );
		return $this->html->element( 'div', $element, array( 'class' => 'ugc-input-wrapper' ), false );
		break;
	case 'input':
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
		// @todo implement select and checkboxes
		// For now additional customization is available via do_action( 'fu_additional_html' );
	default:
		endswitch;
	}

	/**
	 * Display the upload post form
	 *
	 * @param array   $atts    shortcode attributes
	 * @param string  $content content that is encloded in [fe-upload-form][/fe-upload-form]
	 */
	function upload_form( $atts, $content = null ) {
		extract( shortcode_atts( array(
					'description' => '',
					'title' => __( 'Create a new post', 'frontend-uploader' ),
					'type' => '',
					'class' => 'validate',
					'category' => '1',
					'success_page' => '',
					'form_layout' => ''
				), $atts ) );
		global $post;
		if ( $form_layout != "post" && $form_layout != "post_image" ) $form_layout="image";

		ob_start();
?>
	<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post" id="ugc-media-form" class="<?php echo esc_attr( $class )?>" enctype="multipart/form-data">
	  <div class="ugc-inner-wrapper">
		  <h2><?php echo esc_html( $title ) ?></h2>
<?php
		if ( !empty( $_GET['response'] ) ) {
			echo $this->user_response( $_GET['response'] );
		}

		// if there's no user response or if we've encountered a problem we have to show the form again
		if ( empty( $_GET['response'] ) || $_GET['response'] == "ugc-disallowed_mime_type" || $_GET['response'] == "nonce-failure" ) {

			// We have some customizations, nice!
			// Let's parse them
			if ( $content ):
				echo do_shortcode( $content );
			// Or render default form
			else:
				$textarea_desc = __( 'Description (Required)', 'frontend-uploader' );
			$file_desc = __( 'Your Photo', 'frontend-uploader' );
			$submit_button = __( 'Submit', 'frontend-uploader' );

			echo do_shortcode ( '[input type="text" name="post_title" id="ug_post_title" description="Title (Required)" class="required"]' );

			// here we select the different fields based on the form layout to allow for different types
			// of uploads (only a file, only a post or a file and post)
			if ( $form_layout=="post_image" )
				echo do_shortcode( '[textarea name="post_content" class="textarea" id="ug_content" class="required" description="'. $textarea_desc .'"]
						    [input type="file" name="photo" id="ug_photo" description="'. $file_desc .'" multiple=""]' );
			elseif ( $form_layout=="post" )
				echo do_shortcode( '[textarea name="post_content" class="textarea" id="ug_content" class="required" description="'. $textarea_desc .'"]' );
			else
				echo do_shortcode( '[textarea name="caption" class="textarea" id="ug_caption" description="'. $textarea_desc .'"]
								[input type="file" name="photo" id="ug_photo" class="required" description="'. $file_desc .'" multiple=""]' );

			if ( isset( $this->settings['show_author'] )  && $this->settings['show_author'] )
				echo do_shortcode ( '[input type="text" name="post_author" id="ug_post_author" description="Author" class=""]' );

			if ( $form_layout=="post_image" or $form_layout=="image" )
				echo do_shortcode ( '[input type="text" name="post_credit" id="ug_post_credit" description="Credit" class=""]' );

			echo do_shortcode ( '[input type="submit" class="btn" value="'. $submit_button .'"]' );


?>
<?php endif; ?>
		  <input type="hidden" name="action" value="upload_ugpost" />
		  <input type="hidden" value="<?php echo $post->ID ?>" name="post_ID" />
		  <input type="hidden" value="<?php echo $category; ?>" name="post_category" />
		  <input type="hidden" value="<?php echo $success_page; ?>" name="success_page" />
		  <input type="hidden" value="<?php echo $form_layout; ?>" name="form_layout" />
		  <input type="hidden" value="" name="session_id" />

		  <?php
			// Allow a little customization
			do_action( 'fu_additional_html' );
?>
		  <?php wp_nonce_field( 'upload_ugphoto', 'nonceugphoto' ); ?>
		  <div class="clear"></div>
<?php
		}
?>

	  </div>
	  </form>
<?php
		return ob_get_clean();
	}

	/**
	 * Render notice for user
	 */
	function user_response( $response ) {
		if ( empty( $response ) )
			return;
		switch ( $response ) {
		case 'ugc-sent':
			$title = __( 'Your file was successfully uploaded!', 'frontend-uploader' );
			$class = 'success';
			break;
		case 'ugc-post-sent':
			$title = __( 'Your post was successfully sent!', 'frontend-uploader' );
			$class = 'success';
			break;
		case 'nonce-failure':
			$title = __( 'Security check failed', 'frontend-uploader' );
			$class = 'failure';
			break;
		case 'ugc-disallowed_mime_type':
			$title = __( 'This kind of file is not allowed. Please, try again selecting other file.', 'frontend-uploader' ) . "\n";
			if ( isset( $_GET['mime'] ) )
				$title .= __( 'The file has following MIME-type:', 'frontend-uploader' ) . esc_attr( $_GET['mime'] );
			$class = 'failure';
			break;
		case 'invalid_post':
			$title = __( 'The content you are trying to post is invalid.', 'frontend-uploader' );
			$class = 'failure';
			break;
		default:
			$title = '';
		}
		return "<p class='ugc-notice {$class}'>$title</p>";
	}

	/**
	 * Enqueue our assets
	 */
	function enqueue_scripts() {

		wp_enqueue_style( 'frontend-uploader', UGC_URL . 'lib/css/frontend-uploader.css' );
		wp_enqueue_script( 'jquery-validate', '//ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'frontend-uploader-js', UGC_URL . 'lib/js/frontend-uploader.js', array( 'jquery', 'jquery-validate' ) );

		// Include localization strings for default messages of validation plugin
		if ( '' != WPLANG ) {
			$lang = explode( '_', WPLANG );
			$url = "//ajax.aspnetcdn.com/ajax/jquery.validate/1.9/localization/messages_{$lang[0]}.js";
			wp_enqueue_script( 'jquery-validate-messages', $url, array( 'jquery' ) );
		}

	}

	/**
	 * 3.5 brings new Media UI
	 * Unfortunately, we have to specify ids of approved attachments explicitly,
	 * Otherwise, editors have to pick photos after they have already approved them in "Manage UGC"
	 *
	 * This method will search a parent post with a regular expression, and update gallery shortcode with freshly approved attachment ID
	 *
	 * @return [type] [description]
	 */
	function update_35_gallery_shortcode( $post_id, $attachment_id ) {
		global $wp_version;
		if ( round( $wp_version, 1 ) >= 3.5 && (int) $post_id != 0 ) {
			$parent = get_post( $post_id );
			preg_match( '#(?<before>(.*))\[gallery(.*)ids=(\'|")(?<ids>[0-9,]*)(\'|")](?<after>(.*))#ims', $parent->post_content, $matches ) ;
			if ( isset( $matches['ids'] ) ) {
				// @todo account for other possible shortcode atts
				$gallery = '[gallery ids="' . $matches['ids'] . ',' . (int) $attachment_id .'"]';
				$post_to_update = array(
					'ID' => (int) $post_id,
					'post_content' => $matches['before'] . $gallery . $matches['after']
				);
				wp_update_post( $post_to_update );
			}

		}
		return;
	}

}

global $frontend_uploader;
$frontend_uploader = new Frontend_Uploader;

