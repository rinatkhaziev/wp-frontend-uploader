<?php
/**
 * Posts Library List Table class.
 *
 * @todo Unhack
 *
 */
require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . '/wp-admin/includes/class-wp-posts-list-table.php';
class FU_WP_Posts_List_Table extends WP_Posts_List_Table {

	function __construct() {
		$screen = get_current_screen( 'page' );
		if ( $screen->post_type == '' ) {
			$screen->post_type ='post';
		}
		parent::__construct( array( 'screen' => $screen ) );

	}

	function prepare_items() {
		global $lost, $wpdb, $wp_query, $post_mime_types, $avail_post_mime_types;

		$q = $_REQUEST;

		if ( !empty( $lost ) )
			$q['post__in'] = implode( ',', $lost );

		add_filter( 'posts_where', array( &$this, 'modify_post_status_to_private' ) );

		list( $post_mime_types, $avail_post_mime_types ) = wp_edit_attachments_query( $q );
		$this->is_trash = isset( $_REQUEST['status'] ) && 'trash' == $_REQUEST['status'];
		$this->set_pagination_args( array(
				'total_items' => $wp_query->found_posts,
				'total_pages' => $wp_query->max_num_pages,
				'per_page' => $wp_query->query_vars['posts_per_page'],
			) );
		$this->items = $wp_query->posts;

		$columns = $this->get_columns();

		$hidden = array(
			'id',
		);
		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() ) ;

		remove_filter( 'posts_where', array( &$this, 'modify_post_status_to_private' ) );
	}


	function modify_post_status_to_private( $where ) {
		global $wpdb;
		return "AND $wpdb->posts.post_type = '{$this->screen->post_type}' AND ($wpdb->posts.post_status = 'inherit' OR $wpdb->posts.post_status = 'private') ";

	}

	function get_views() {
		global $wpdb, $post_mime_types, $avail_post_mime_types;
		$type_links = array();
		$_num_posts = (array) wp_count_attachments();

		$_total_posts = array_sum( $_num_posts ) - $_num_posts['trash'];
		if ( !isset( $total_orphans ) )
			$total_orphans = $wpdb->get_var( "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1" );
		$matches = wp_match_mime_types( array_keys( $post_mime_types ), array_keys( $_num_posts ) );
		foreach ( $matches as $type => $reals )
			foreach ( $reals as $real )
				$num_posts[$type] = ( isset( $num_posts[$type] ) ) ? $num_posts[$type] + $_num_posts[$real] : $_num_posts[$real];

			$class = ( empty( $_GET['post_mime_type'] ) && !isset( $_GET['status'] ) ) ? ' class="current"' : '';
		$type_links['all'] = "<a href='upload.php'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $_total_posts, 'uploaded files' ), number_format_i18n( $_total_posts ) ) . '</a>';
		foreach ( $post_mime_types as $mime_type => $label ) {
			$class = '';

			if ( !wp_match_mime_types( $mime_type, $avail_post_mime_types ) )
				continue;

			if ( !empty( $_GET['post_mime_type'] ) && wp_match_mime_types( $mime_type, $_GET['post_mime_type'] ) )
				$class = ' class="current"';
			if ( !empty( $num_posts[$mime_type] ) )
				$type_links[$mime_type] = "<a href='upload.php?post_mime_type=$mime_type'$class>" . sprintf( translate_nooped_plural( $label[2], $num_posts[$mime_type] ), number_format_i18n( $num_posts[$mime_type] ) ) . '</a>';
		}

		if ( !empty( $_num_posts['trash'] ) )
			$type_links['trash'] = '<a href="upload.php?status=trash"' . ( ( isset( $_GET['status'] ) && $_GET['status'] == 'trash' ) ? ' class="current"' : '' ) . '>' . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $_num_posts['trash'], 'uploaded files' ), number_format_i18n( $_num_posts['trash'] ) ) . '</a>';

		return array();
	}



	function get_bulk_actions() {
		$actions = array();
		$actions['delete'] = __( 'Delete Permanently', 'frontend-uploader' );
		//if ( $this->detached )
		// $actions['attach'] = __( 'Attach to a post', 'frontend-uploader' );

		return $actions;
	}

	function current_action() {

		if ( isset( $_REQUEST['find_detached'] ) )
			return 'find_detached';

		if ( isset( $_REQUEST['found_post_id'] ) )
			return 'attach';

		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) )
			return 'delete_all';

		return parent::current_action();
	}

	function has_items() {
		return have_posts();
	}

	function no_items() {
		__( 'No media attachments found.', 'frontend-uploader' );
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


	function _get_row_actions( $post, $att_title ) {
		$actions = array();

		if ( current_user_can( 'edit_post', $post->ID ) )
			$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '">' . __( 'Edit', 'frontend-uploader' ) . '</a>';

		if ( $post->post_status == 'private' ) {
			$actions['pass'] = '<a href="'.admin_url( 'admin-ajax.php' ).'?action=approve_ugc_post&id=' . $post->ID . '&post_type=' . $post->post_type . '">'. __( 'Approve', 'frontend-uploader' ) .'</a>';
		}

		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ( 'trash' == $post->post_status )
				$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash' ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ) . "'>" . __( 'Restore' ) . "</a>";
			elseif ( EMPTY_TRASH_DAYS )
				$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash' ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash' ) . "</a>";
			if ( 'trash' == $post->post_status || !EMPTY_TRASH_DAYS )
				$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
		}

		$actions['view'] = '<a href="' . get_permalink( $post->ID ) . '" title="' . esc_attr( sprintf( __( 'View "%s"', 'frontend-uploader' ), $att_title ) ) . '" rel="permalink">' . __( 'View', 'frontend-uploader' ) . '</a>';

		return $actions;
	}
}
