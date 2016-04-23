<?php
/**
 * Media Library List Table class.
 *
 */
require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . '/wp-admin/includes/class-wp-media-list-table.php';

class FU_WP_Media_List_Table extends WP_Media_List_Table {

	function __construct() {
		parent::__construct();
	}

	function prepare_items() {
		global $lost, $wpdb, $wp_query, $post_mime_types, $avail_post_mime_types;

		add_filter( 'posts_where', array( $this, 'modify_post_status_to_private' ) );

		parent::prepare_items();

		$this->items = $wp_query->posts;
		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$hidden = array(
			'id',
		);

		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() ) ;

		add_filter( 'media_row_actions', array( $this, 'filter_media_row_actions' ), 9, 3);

		remove_filter( 'posts_where', array( $this, 'modify_post_status_to_private' ) );
	}

	function filter_media_row_actions( $actions, $post, $detached ) {
		$detached = $post->post_parent === 0;
		$actions['pass'] = '<a href="' . admin_url( 'admin-ajax.php' ).'?action=approve_ugc&id=' . $post->ID . '&fu_nonce=' . wp_create_nonce( FU_NONCE ). '">'. __( 'Approve', 'frontend-uploader' ) .'</a>';

		if ( ! $detached ) {
			$actions['re-attach'] = sprintf( '<a class="hide-if-no-js" onclick="findPosts.open( \'media[]\', \'%d\' );return false;" href="#the-list">%s</a>',
				$post->ID, esc_html( __( 'Re-Attach', 'frontend-uploader' ) )
			);
		}

		return $actions;
	}

	function modify_post_status_to_private( $where ) {
		return str_replace( "post_status = 'inherit' ", "post_status = 'private' ", $where );
	}

}