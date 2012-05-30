<?php
/*
Plugin Name: UGC Frontend Uploader
Description: Allow your visitors to upload content and moderate it.
Author: Rinat Khaziev
Version: 0.1.1
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
define( 'UGC_VERSION', '0.1.1' );
define( 'UGC_ROOT' , dirname( __FILE__ ) );
define( 'UGC_FILE_PATH' , UGC_ROOT . '/' . basename( __FILE__ ) );
define( 'UGC_URL' , plugins_url( '/', __FILE__ ) );

require_once( UGC_ROOT . '/lib/php/class-frontend-uploader-wp-media-list-table.php' );
require_once( UGC_ROOT . '/lib/php/class-html-helper.php' );

class Frontend_Uploader {

	public $allowed_mime_types;
	public $html;
	
	function __construct() {
		// Hooking to wp_ajax
		add_action( 'wp_ajax_upload_ugphoto', array( $this, 'upload_photo' ) );
		add_action( 'wp_ajax_nopriv_upload_ugphoto', array( $this, 'upload_photo' ) );
		add_action( 'wp_ajax_approve_ugc', array( $this, 'approve_photo' ) );
		// Adding media submenu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		
		// Adding our shortcodes
		add_shortcode( 'fu-upload-form', array( $this, 'upload_form' ) );
		add_shortcode( 'input', array( $this, 'shortcode_content_parser' ) );
		add_shortcode( 'textarea', array( $this, 'shortcode_content_parser' ) );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Configuration filter:
		// fu_allowed_mime_types should return array of allowed mime types
		$this->allowed_mime_types = apply_filters( 'fu_allowed_mime_types', array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif') );
		// HTML helper to render HTML elements
		$this->html = new Html_Helper;
	}

	/**
	 * Handles the upload of a user's photo
	 */
	function upload_photo() {
	$media_ids = array(); // will hold uploaded media IDS

	if ( !wp_verify_nonce( $_POST['nonceugphoto'], 'upload_ugphoto' ) )  {
		wp_redirect ( add_query_arg( array( 'response' => 'nonce-failure' ), $_POST['_wp_http_referer'] ) );
		exit;
	} // if nonce is invalid, redirect to referer and display error flash notice

	if ( !empty( $_FILES ) && intval( $_POST['post_ID'] ) != 0 ) {
		foreach ( $_FILES as $k => $v ) {
			// Iterate through files, and save upload if it's one of allowed MIME types
			if ( in_array( $v['type'], $this->allowed_mime_types ) )  {
				// Setup some default values
				// However, you can make additional changes on 'fu_after_upload' action
				$post_overrides = array(
					'post_status' => 'private',
					'post_title' => isset( $_POST['caption'] ) && ! empty( $_POST['caption'] ) ? filter_var( $_POST['caption'], FILTER_SANITIZE_STRING ) : 'Unnamed',
					'post_content' => !empty( $_POST['name'] ) ? 'Courtesy of ' . filter_var($_POST['name'], FILTER_SANITIZE_STRING) : '',
				);
				$media_ids[] =  media_handle_upload( $k, intval( $_POST['post_ID'] ), $post_overrides );
			}
		} 
	}
	
	// Allow additional setup
	// Pass array of attachment ids 
	do_action( 'fu_after_upload', $media_ids );
	
	if ( $_POST['_wp_http_referer'] )
	  wp_redirect ( add_query_arg( array( 'response' => 'ugc-sent' ), $_POST['_wp_http_referer'] ) );
	  exit;
  }

	function admin_list() {
		$title = 'Manage UGC';
		set_current_screen( 'upload' );
		if ( !current_user_can( 'upload_files' ) )
			wp_die( __( 'You do not have permission to upload files.' ) );

		$wp_list_table = new FE_WP_Media_List_Table();

		$pagenum = $wp_list_table->get_pagenum();
		$doaction = $wp_list_table->current_action();
		$wp_list_table->prepare_items();
		wp_enqueue_script( 'wp-ajax-response' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'media' );
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?> <a href="media-new.php" class="add-new-h2"><?php echo esc_html_x('Add New', 'file'); ?></a> <?php
if ( isset($_REQUEST['s']) && $_REQUEST['s'] )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', get_search_query() ); ?>
</h2>

<?php
$message = '';
if ( isset($_GET['posted']) && (int) $_GET['posted'] ) {
	$message = __('Media attachment updated.');
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
}

if ( isset($_GET['attached']) && (int) $_GET['attached'] ) {
	$attached = (int) $_GET['attached'];
	$message = sprintf( _n('Reattached %d attachment.', 'Reattached %d attachments.', $attached), $attached );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('attached'), $_SERVER['REQUEST_URI']);
}

if ( isset($_GET['deleted']) && (int) $_GET['deleted'] ) {
	$message = sprintf( _n( 'Media attachment permanently deleted.', '%d media attachments permanently deleted.', $_GET['deleted'] ), number_format_i18n( $_GET['deleted'] ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('deleted'), $_SERVER['REQUEST_URI']);
}

if ( isset($_GET['trashed']) && (int) $_GET['trashed'] ) {
	$message = sprintf( _n( 'Media attachment moved to the trash.', '%d media attachments moved to the trash.', $_GET['trashed'] ), number_format_i18n( $_GET['trashed'] ) );
	$message .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __('Undo') . '</a>';
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('trashed'), $_SERVER['REQUEST_URI']);
}

if ( isset($_GET['untrashed']) && (int) $_GET['untrashed'] ) {
	$message = sprintf( _n( 'Media attachment restored from the trash.', '%d media attachments restored from the trash.', $_GET['untrashed'] ), number_format_i18n( $_GET['untrashed'] ) );
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('untrashed'), $_SERVER['REQUEST_URI']);
}

if (isset($_GET['approved'])) {
  $message = 'The photo was approved';
}

$messages[1] = __('Media attachment updated.');
$messages[2] = __('Media permanently deleted.');
$messages[3] = __('Error saving media attachment.');
$messages[4] = __('Media moved to the trash.') . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" ) ) . '">' . __('Undo') . '</a>';
$messages[5] = __('Media restored from the trash.');

if ( isset($_GET['message']) && (int) $_GET['message'] ) {
	$message = $messages[$_GET['message']];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

if ( !empty($message) ) { ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php } ?>

<?php $wp_list_table->views(); ?>

<form id="posts-filter" action="" method="get">

<?php $wp_list_table->search_box( __( 'Search Media' ), 'media' ); ?>

<?php $wp_list_table->display(); ?>

<div id="ajax-response"></div>
<?php find_posts_div(); ?>
<br class="clear" />

</form>
</div>


<?php
	}

	function add_menu_item() {
		add_media_page('Manage UGC', 'Manage UGC', 'edit_posts', 'manage_frontend_uploader', array( $this, 'admin_list' ) );
	}

	function approve_photo() {
		// check for permissions and id
		if ( !current_user_can( 'edit_posts' ) || intval( $_GET['id'] ) == 0 || !wp_verify_nonce( 'nonceugphoto', 'upload_ugphoto' ) )
			wp_redirect( get_admin_url( null,'upload.php?page=manage_frontend_uploader&error=id_or_perm' ) );
	
		$post = get_post($_GET['id']);
	
		if ( is_object( $post ) && $post->post_status == 'private') {
			$post->post_status = 'inherit';
			wp_update_post( $post );
			wp_redirect( get_admin_url( null,'upload.php?page=manage_frontend_uploader&approved=1' ) );
		}
	
		wp_redirect( get_admin_url( null,'upload.php?page=manage_frontend_uploader' ) );
		exit;
	}
	
	/**
	 * Shortcode callback for inner content of [fu-upload-form] shortcode
	 *
	 * @param array $atts shortcode attributes
	 * @param $content not used
	 * @param string $tag
	 */
	function shortcode_content_parser( $atts, $content = null, $tag ) {
		extract( shortcode_atts( array(
			'id' => '',
			'name' => '',
			'description' => '',
			'value' => '',
			'type' => '',
			'class' => '',
		), $atts ) );
		switch ( $tag ):
			case 'textarea':
				echo $this->html->element( 'label',
					$description .
					$this->html->element('textarea', '', array( 'name' => $name, 'id' => $id, 'class' => $class ) )
					,
					array('for' => $id ),
					false );
			break;
			case 'input':
				echo $this->html->element( 'label',
					$description .
					$this->html->input( $type, $name, $value, array('id' => $id, 'class' => $class ) )
					,
					array('for' => $id ),
					false );
			// @todo implement select and checkboxes
			// For now additional customization is available via do_action( 'fu_additional_html' );
		endswitch;
	}
	
	/**
	 * Display the upload form
	 *
	 * @param array $atts shortcode attributes
	 * @param string $content content that is encloded in [fe-upload-form][/fe-upload-form]
	 */
	function upload_form( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'description' => '',
			'title' => 'Upload a photo',
			'type' => '',
			'class' => 'validate',
		), $atts ) );	
	global $post;

?>		
	<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" method="post" id="ugc-media-form" class="<?php echo esc_attr( $class )?>" enctype="multipart/form-data">
	  <div class="ugc-inner-wrapper">
		  <h2><?php echo esc_html( $title ) ?></h2>		  
<?php
		echo $this->user_response();
		// We have some customizations, nice!
		// Let's parse them
		if ( $content ):
			do_shortcode( $content );
		// Or render default form	
		else:
			do_shortcode( ' [textarea name="caption" class="textarea" id="ug_caption" description="Description (optional)"]	   
						    [input type="file" name="photo" id="ug_photo" class="required" description="Your Photo"]
							[input type="submit" class="btn" value="Submit"]');
?>	  		
<?php endif; ?>		  
		  <input type="hidden" name="action" value="upload_ugphoto" />
		  <input type="hidden" value="<?php echo $post->ID ?>" name="post_ID" />
		  <?php
		  // Allow a little customization
		  do_action( 'fu_additional_html' );
		  ?>
		  <?php wp_nonce_field( 'upload_ugphoto', 'nonceugphoto' ); ?>
		  <div class="clear"></div>
	  </div>
	  </form>
<?php						
	}
	/**
	 * Render notice for user
	 */
	function user_response() {
		if ( empty( $_GET['response'] ) ) 
			return;
		switch($_GET['response']) {
			case 'ugc-sent':
				$title ='Your file was successfully uploaded!';
			break;
			case 'nonce-failure':
				$title = 'Security check failed';
			break;
			default:
			    $title = '';
		}
		return "<p>$title</p>";
	}
	
	/**
	 * Enqueue our assets
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'frontend-uploader', UGC_URL . '/lib/css/frontend-uploader.css' );
		wp_enqueue_script( 'jquery-validate', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'frontend-uploader-js', UGC_URL . '/lib/js/frontend-uploader.js', array( 'jquery', 'jquery-validate' ) );
	}
	
}

global $frontend_uploader;
$frontend_uploader = new Frontend_Uploader;
?>