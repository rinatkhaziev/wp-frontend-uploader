<?php
/**
 * Posts Library List Table class.
 *
 * @todo refactor display_rows() using single row and callbacks
 *
 */
require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . '/wp-admin/includes/class-wp-posts-list-table.php';
class FU_WP_Posts_List_Table extends WP_Posts_List_Table {

	function __construct() {
		parent::__construct( array( 'screen' => get_current_screen() ) );
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

		//also making it posts only
		return "AND $wpdb->posts.post_type = 'post' AND ($wpdb->posts.post_status = 'inherit' OR $wpdb->posts.post_status = 'private') ";

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

	function display_rows() {
		global $post, $id;

		add_filter( 'the_title', 'esc_html' );
		$alt = '';

		while ( have_posts() ) : the_post();
		if ( $this->is_trash && $post->post_status != 'trash' || !$this->is_trash && $post->post_status == 'trash' )
			continue;

		$alt = ( 'alternate' == $alt ) ? '' : 'alternate';
		$post_owner = ( get_current_user_id() == $post->post_author ) ? 'self' : 'other' ;
		$att_title = _draft_or_post_title();
?>
	<tr id='post-<?php echo $id; ?>' class='<?php echo trim( $alt . ' author-' . $post_owner . ' status-' . $post->post_status ); ?>' valign="top">
<?php

		list( $columns, $hidden ) = $this->get_column_info();
		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = $class . $style;

			switch ( $column_name ) {

			case 'cb':
?>
		<th scope="row" class="check-column"><?php if ( current_user_can( 'edit_post', $post->ID ) ) { ?><input type="checkbox" name="media[]" value="<?php the_ID(); ?>" /><?php } ?></th>
<?php
				break;

			case 'icon':
				$attributes = 'class="column-icon posts-icon"' . $style;
?>
		<td <?php echo $attributes ?>><?php
				if ( $thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true ) ) {
					if ( $this->is_trash ) {
						echo $thumb;
					} else {
?>
				<a href="<?php echo get_edit_post_link( $post->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit "%s"', 'frontend-uploader' ), $att_title ) ); ?>">
					<?php echo $thumb; ?>
				</a>

<?php   }
				}
?>
		</td>
<?php
				break;

			case 'title':
?>
		<td <?php echo $attributes ?>><strong><?php if ( $this->is_trash ) echo $att_title; else { ?><a href="<?php echo get_edit_post_link( $post->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit "%s"', 'frontend-uploader' ), $att_title ) ); ?>"><?php echo $att_title; ?></a><?php };  _media_states( $post ); ?></strong>
			<p>
<?php
				if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $post->ID ), $matches ) )
					echo esc_html( strtoupper( $matches[1] ) );
				else
					echo strtoupper( str_replace( 'image/', '', get_post_mime_type() ) );
?>
			</p>
<?php
				echo $this->row_actions( $this->_get_row_actions( $post, $att_title ) );
?>
		</td>
<?php
				break;

			case 'author':
?>
		<td <?php echo $attributes ?>><?php the_author() ?></td>
<?php
				break;

			case 'tags':
?>
		<td <?php echo $attributes ?>><?php
				$tags = get_the_tags();
				if ( !empty( $tags ) ) {
					$out = array();
					foreach ( $tags as $c )
						$out[] = "<a href='edit.php?tag=$c->slug'> " . esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'post_tag', 'display' ) ) . "</a>";
					echo join( ', ', $out );
				} else {
					__( 'No Tags', 'frontend-uploader' );
				}
?>
		</td>
<?php
				break;

			case 'desc':
?>
		<td <?php echo $attributes ?>><?php echo has_excerpt() ? $post->post_excerpt : ''; ?></td>
<?php
				break;

			case 'date':
				if ( '0000-00-00 00:00:00' == $post->post_date && 'date' == $column_name ) {
					$t_time = $h_time = __( 'Unpublished', 'frontend-uploader' );
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'frontend-uploader' ) );
					$m_time = $post->post_date;
					$time = get_post_time( 'G', true, $post, false );
					if ( ( abs( $t_diff = time() - $time ) ) < 86400 ) {
						if ( $t_diff < 0 )
							$h_time = sprintf( __( '%s from now', 'frontend-uploader' ), human_time_diff( $time ) );
						else
							$h_time = sprintf( __( '%s ago', 'frontend-uploader' ), human_time_diff( $time ) );
					} else {
						$h_time = mysql2date( __( 'Y/m/d', 'frontend-uploader' ), $m_time );
					}
				}
?>
		<td <?php echo $attributes ?>><?php echo $h_time ?></td>
<?php
				break;

			case 'parent':
				if ( $post->post_parent > 0 ) {
					if ( get_post( $post->post_parent ) ) {
						$title =_draft_or_post_title( $post->post_parent );
					}
?>
			<td <?php echo $attributes ?>>
				<strong><a href="<?php echo get_edit_post_link( $post->post_parent ); ?>"><?php echo $title ?></a></strong>,
				<?php echo get_the_time( __( 'Y/m/d', 'frontend-uploader' ) ); ?>
			</td>
<?php
				} else {
?>
			<td <?php echo $attributes ?>><?php __( '(Unattached)', 'frontend-uploader' ); ?><br />
			<a class="hide-if-no-js" onclick="findPosts.open( 'media[]','<?php echo $post->ID ?>' );return false;" href="#the-list"><?php _e_( 'Attach', 'frontend-uploader' ); ?></a></td>
<?php
				}
				break;



			default:
?>
		<td <?php echo $attributes ?>>
			<?php do_action( 'manage_fu_posts_custom_column', $column_name, $id ); ?>
		</td>
<?php
				break;
			}
		}
?>
	</tr>
<?php endwhile;
	}

	function _get_row_actions( $post, $att_title ) {
		$actions = array();

		if ( current_user_can( 'edit_post', $post->ID ) )
			$actions['edit'] = '<a href="' . get_edit_post_link( $post->ID, true ) . '">' . __( 'Edit', 'frontend-uploader' ) . '</a>';

		if ( $post->post_status == 'private' ) {
			$actions['pass'] = '<a href="'.admin_url( 'admin-ajax.php' ).'?action=approve_ugc_post&id=' . $post->ID . '">'. __( 'Approve', 'frontend-uploader' ) .'</a>';
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
