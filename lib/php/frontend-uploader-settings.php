<?php
/**
 *
 */

class Frontend_Uploader_Settings {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;

		add_action( 'current_screen', array( $this, 'action_current_screen' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

	}
	/**
	 * Only run if current screen is plugin settings or options.php
	 * @return [type] [description]
	 */
	function action_current_screen() {
		$screen = get_current_screen();
		if ( in_array( $screen->base, array( 'settings_page_fu_settings', 'options' ) ) ) {
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );
			//initialize settings
			$this->settings_api->admin_init();
		}
		//set the settings
	}

	function action_admin_menu() {
		add_options_page( __( 'Frontend Uploader Settings', 'frontend-uploader' ) , __( 'Frontend Uploader Settings', 'frontend-uploader' ), 'manage_options', 'fu_settings', array( $this, 'plugin_page' ) );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id' => 'frontend_uploader_settings',
				'title' => __( 'Basic Settings', 'frontend-uploader' ),
			),
		);
		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	function get_settings_fields() {
		$settings_fields = array(
			'frontend_uploader_settings' => array(
				array(
					'name' => 'notify_admin',
					'label' => __( 'Notify site admins', 'frontend-uploader' ),
					'desc' => __( 'Yes', 'frontend-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
				array(
					'name' => 'admin_notification_text',
					'label' => __( 'Admin Notification', 'frontend-uploader' ),
					'desc' => __( 'Message that admin will get on new file upload', 'frontend-uploader' ),
					'type' => 'textarea',
					'default' => 'Someone uploaded a new UGC file, please moderate at: ' . admin_url( 'upload.php?page=manage_frontend_uploader' ),
				),
				array(
					'name' => 'notification_email',
					'label' => __( 'Notification email', 'frontend-uploader' ),
					'desc' => __( 'Leave blank to use site admin email', 'frontend-uploader' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'name' => 'allowed_categories',
					'label' => __( 'Allowed categories', 'frontend-uploader' ),
					'desc' => __( 'Comma separated IDs (leave blank for all)', 'frontend-uploader' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'name' => 'user_verification',
					'label' => __( 'User Verification callback', 'frontend-uploader' ),
					'desc' => __( 'Leave blank for none', 'frontend-uploader' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'name' => 'show_author',
					'label' => __( 'Show author field', 'frontend-uploader' ),
					'desc' => __( 'Yes', 'frontend-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
				array(
					'name' => 'wysiwyg_enabled',
					'label' => __( 'Enable visual editor', 'frontend-uploader' ),
					'desc' => __( 'Yes', 'frontend-uploader' ),
					'type' => 'checkbox',
					'default' => '',
				),
			),
		);

		return $settings_fields;
	}

	function plugin_page() {
		echo '<div class="wrap">';
		settings_errors();

		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();

		echo '</div>';
	}

	/**
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
	function get_pages() {
		$pages = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[$page->ID] = $page->post_title;
			}
		}

		return $pages_options;
	}

}

$frontend_uploader_settings = new Frontend_Uploader_Settings;