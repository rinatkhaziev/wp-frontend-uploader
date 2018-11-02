<?php
/**
 * Posts Library List Table class.
 *
 */
class FU_WP_Posts_List_Table extends WP_Posts_List_Table {

	function __construct( $args = array() ) {
		global $frontend_uploader;

		$screen = get_current_screen();
		if ( $screen->post_type === '' ) {
			$screen->post_type ='post';
		}

		add_filter( "{$screen->post_type}_row_actions", array( $this, '_add_row_actions' ), 10, 2 );

		parent::__construct( array( 'screen' => $screen ) );
	}

	function _add_row_actions( $actions, $post ) {
		unset( $actions['inline hide-if-no-js'] );
		if ( $post->post_status === 'private' ) {
			$actions['pass'] = '<a href="'.admin_url( 'admin-ajax.php' ).'?action=approve_ugc_post&id=' . $post->ID . '&post_type=' . $post->post_type . '">'. __( 'Approve', 'frontend-uploader' ) .'</a>';
			$actions['delete'] = '<a onclick="return showNotice.warn();" href="'.admin_url( 'admin-ajax.php' ).'?action=delete_ugc&id=' . $post->ID . '&post_type=' . $post->post_type . '&fu_nonce=' . wp_create_nonce( FU_NONCE ). '">'. __( 'Delete Permanently', 'frontend-uploader' ) .'</a>';
		}
		return $actions;
	}

	/**
	* WP_Posts_List_Table is loaded in a different matter and WP_Posts_List::prepare_items() calls wp
	* And we don't want that, so the query is set with query_posts in Frontend_Uploader::_set_global_query_for_tables()
	*/
	function prepare_items() {
		global $lost, $wpdb, $wp_query, $post_mime_types, $avail_post_mime_types;

		$this->items = $wp_query->posts;

		$columns = $this->get_columns();

		$hidden = array(
			'id',
		);

		$this->set_pagination_args( array(
			'total_items' => $wp_query->found_posts,
			'total_pages' => $wp_query->max_num_pages,
			'per_page' => $wp_query->query_vars['posts_per_page'],
		) );

		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() ) ;
	}

	function get_views() {
		return array();
	}

	function get_bulk_actions() {
		$actions = array();
		$actions['delete'] = __( 'Delete Permanently', 'frontend-uploader' );
		return $actions;
	}

	function get_columns() {

		$posts_columns = array();
		$posts_columns['cb'] = '<input type="checkbox" />';
		$posts_columns['title'] = _x( 'Title', 'column name' );

		$posts_columns['categories'] = _x( 'Categories', 'column name' );

		$posts_columns['date'] = _x( 'Date', 'column name' );
		$posts_columns = apply_filters( 'manage_fu_posts_columns', $posts_columns );

		return $posts_columns;
	}

}
