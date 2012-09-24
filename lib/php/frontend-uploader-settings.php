<?php
/**
 *
 */

class Frontend_Uploader_Settings {

	private $settings_api, $default_mime_types;

	function __construct() {
		$this->settings_api = WeDevs_Settings_API::getInstance();

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		$this->default_mime_types = array(
			'jpg|jpeg|jpe' => 'jpg|jpeg|jpe',
			'gif' => 'gif',
			'png' => 'png',
			'tif|tiff' => 'tif|tiff',
			'asf|asx|wax|wmv|wmx' => 'asf|asx|wax|wmv|wmx',
			'avi' => 'avi',
			'divx' => 'divx',
			'flv' => 'flv',
			'mov|qt' => 'mov|qt',
			'mpeg|mpg|mpe' => 'mpeg|mpg|mpe',
			'mp3|m4a|m4b' => 'mp3|m4a|m4b',
			'mp4|m4v' => 'mp4|m4v',
			'ra|ram' => 'ra|ram',
			'wav' => 'wav',
			'ogg|oga' => 'ogg|oga',
			'ogv' => 'ogv',
			'mid|midi' => 'mid|midi',
			'wma' => 'wma',
			'mka' => 'mka',
			'mkv' => 'mkv',
			'pdf' => 'pdf',
			'doc|docx' => 'doc|docx',
			'pot|pps|ppt|pptx|ppam|pptm|sldm|ppsm|potm' => 'pot|pps|ppt|pptx|ppam|pptm|sldm|ppsm|potm',
			'wri' => 'wri',
			'xla|xls|xlsx|xlt|xlw|xlam|xlsb|xlsm|xltm' => 'xla|xls|xlsx|xlt|xlw|xlam|xlsb|xlsm|xltm',
			'mdb' => 'mdb',
			'mpp' => 'mpp',
			'docm|dotm' => 'docm|dotm',
			'pptx|sldx|ppsx|potx' => 'pptx|sldx|ppsx|potx',
			'xlsx|xltx' => 'xlsx|xltx',
			'docx|dotx' => 'docx|dotx',
			'onetoc|onetoc2|onetmp|onepkg' => 'onetoc|onetoc2|onetmp|onepkg',
			'odt' => 'odt',
			'odp' => 'odp',
			'ods' => 'ods',
			'odg' => 'odg',
			'odc' => 'odc',
			'odb' => 'odb',
			'odf' => 'odf',
			);
	}

	function admin_init() {

		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );

		//initialize settings
		$this->settings_api->admin_init();
	}

	function admin_menu() {
		add_options_page( __( 'Frontend Uploader Settings', 'frontend-uploader' ) , __( 'Frontend Uploader Settings', 'frontend-uploader' ), 'manage_options', 'fu_settings', array( $this, 'plugin_page' ) );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id' => 'basic_settings',
				'title' => __( 'Basic Settings', 'frontend-uploader' ),
			),
			array(
				'id' => 'mime_settings',
				'title' => __( 'Allowed MIME-types', 'frontend-uploader' ),
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
			'basic_settings' => array(
				array(
					'name' => 'notify_admin',
					'label' => __( 'Notify site admins', 'frontend-uploader' ),
					'desc' => __( 'Yes', 'frontend-uploader' ),
					'type' => 'checkbox'
				),
				array(
					'name' => 'admin_notification',
					'label' => __( 'Admin Notification', 'frontend-uploader' ),
					'desc' => __( 'Message that admin will get on new file upload', 'frontend-uploader' ),
					'type' => 'textarea'
				),
			),
			'mime_settings' => array(
				array(
					'name' => 'multicheck',
					'label' => __( 'Allowed MIME-types', 'frontend-uploader' ),
					'desc' => __( 'Multi checkbox description', 'frontend-uploader' ),
					'type' => 'multicheck',
					'default' => $this->default_mime_types,
					'options' => get_allowed_mime_types()
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