<?php
/*
Plugin Name: LMB^Box MediaPress
Plugin URI: http://lmbbox.com/projects/mediapress/
Description: Adds posting functionality to WordPress for attachments / media.
Author: Thomas Montague
Version: 0.1
Author URI: http://lmbbox.com/
*/

require_once(WP_PLUGIN_DIR . '/lmbbox-wordpress-plugin-api/wordpress-plugin-api.php');

class LMBBox_MediaPress_Core extends WordPress_Plugin_API {

	var $attachment_id				= NULL;
	var $attachment_status			= NULL;
	var $attachment_tags			= NULL;

/* No need to have a class constructor as the parent class will handle everything
	// PHP5 Constructor
	function __construct( $name, $version, $folder, $file ) {
		parent::__construct( $name, $version, $folder, $file );
	}
	// PHP4 Constructor - Just calls PHP5 Constructor
	function LMBBox_Test_Core( $name, $version, $folder, $file ) {
		$this->__construct( $name, $version, $folder, $file );
	}
*/
	
	// Setup Options
	function _setup_options() {
		
	}
	
	// Setup Display Items
	function _setup_display() {		
		$this->_add_menu_page( 'lmbbox_mediapress', 'LMB^Box MediaPress', 'MediaPress', 'administrator', 'media', FALSE, 'lmbbox-mediapress/media.php', '' );
	}
	
	// Setup Hooks
	function _setup_hooks() {
		$this->_add_hook( 'filter', 'attachment_fields_to_edit', array( &$this, 'add_fields_to_edit' ), 11, 2 );
		$this->_add_hook( 'filter', 'attachment_fields_to_save', array( &$this, 'add_fields_to_save' ), 11, 2 );
//		$this->_add_hook( 'action', 'admin_menu', array( &$this, 'modify_menus' ) );
		$this->_add_hook( 'filter', 'get_edit_post_link', array( &$this, 'get_edit_post_link_filter'), 10, 3 );
		$this->_add_hook( 'action', 'edit_attachment', array( &$this, 'edit_attachment_action') );
		$this->_add_hook( 'filter', 'manage_media_columns', array( &$this, 'mediapress_columns') );
		$this->_add_hook( 'action', 'manage_media_custom_column', array( &$this, 'mediapress_custom_column'), 10, 2 );
	}
	
	function modify_menus() {
		global $menu, $submenu;
		
		$menu[10][2] = 'lmbbox-mediapress/media.php';
		unset( $admin_page_hooks['upload.php'] );
		
		$submenu['lmbbox-mediapress/media.php'] = $submenu['upload.php'];
		unset( $submenu['upload.php'] );
		$submenu['lmbbox-mediapress/media.php'][5][2] = 'lmbbox-mediapress/media.php';
	}
	
	function get_edit_post_link_filter( $link, $id, $context ) {
		if ( !$post = &get_post( $id ) )
			return;
		
		if ( 'display' == $context )
			$action = 'action=edit&amp;';
		else
			$action = 'action=edit&';
		
		if ( $post->post_type == 'media' ) {
			if ( !current_user_can( 'edit_post', $post->ID ) )
				return;
			$file = 'media';
			$var  = 'attachment_id';
			
			return admin_url("$file.php?{$action}$var=$post->ID");
		}
		
		return $link;
	}
	


	function mediapress_columns($defaults) {
	    $defaults['categories'] = __('Categories');
	    $defaults['tags'] = __('Tags');
	    $defaults['status'] = __('Status');
	    return $defaults;
	}
	
	function mediapress_custom_column($column_name, $post_id) {
		global $post;
		
		switch ( $column_name ) {
			case 'categories':
//				echo implode(', ', wp_get_post_categories( $post_id ) );
				$categories = get_the_category();
				if ( !empty( $categories ) ) {
					$out = array();
					foreach ( $categories as $c )
						$out[] = "<a href='edit.php?category_name=$c->slug'> " . wp_specialchars(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
						echo join( ', ', $out );
				} else {
					_e('Uncategorized');
				}
				break;
//			case 'tags':
//	    		echo get_tags_to_edit( $post_id );
//				break;
			case 'status':
				if ( 'publish' == $post->post_status ) {
					_e('Published');
				} elseif ( 'inherit' == $post->post_status ) {
					_e('Inherited');
				}
				break;
			default:
				break;
		}
	}


	function add_fields_to_edit( $form_fields, $post ) {
/*
		$form_fields['post_type'] = array(
			'label' => __( 'Post Type' ),
			'value' => '',
			'input' => 'select',
			'select' => '<select id="attachments[' . $post->ID . '][post_type]" name="attachments[' . $post->ID . '][post_type]"><option value="attachment">' . __( 'Attachment' ) . '</option><option value="media">' . __( 'Media' ) . '</option></select>',
			'helps' => ''
		);
*/

		$form_fields['post_status'] = array(
			'label' => __( 'Post Status' ),
			'value' => '',
			'input' => 'select',
			'select' => '<select id="attachments[' . $post->ID . '][post_status]" name="attachments[' . $post->ID . '][post_status]"><option' . ( $post->post_status == 'inherit' ? ' selected="selected"' : '' ) . ' value="inherit">' . __( 'Inherit' ) . '</option><option' . ( $post->post_status == 'publish' ? ' selected="selected"' : '' ) . ' value="publish">' . __( 'Published' ) . '</option></select>',
			'helps' => __( 'Set to Publish for Media Posting.' )
		);


		$walker = new LMBBox_MediaPress_Walker_Category_Checklist;
		$args['selected_cats'] = wp_get_post_categories( $post->ID );
		$args['name'] = 'attachments[' . $post->ID . '][post_category][]';
		$categories = get_categories('get=all');

		$form_fields['post_category'] = array(
			'label' => __( 'Categories' ),
			'input' => 'html',
			'html' => call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args))
		);


		$form_fields['post_tags'] = array(
			'label' => __( 'Tags' ),
			'value' => get_tags_to_edit( $post->ID )
		);

		return $form_fields;
	}
	
	function add_fields_to_save( $post, $attachment ) {
		$this->attachment_id = $post['ID'];
		$this->attachment_status = $attachment['post_status'];
		$this->attachment_tags = $attachment['post_tags'];
		$post['post_status'] = $attachment['post_status'];
		$post['post_category'] = $attachment['post_category'];
		$post['post_tags'] = $attachment['post_tags'];
/*
		if ($attachment['post_type'] == 'media') {
			$post['post_type'] = 'media';
			$post['post_status'] = $attachment['post_status'];
		}
*/
		return $post;
	}
	
	function edit_attachment_action( $id ) {
		global $wpdb;
		
		if ( $id == $this->attachment_id ) {
			$wpdb->update( $wpdb->posts, array( 'post_status' => $this->attachment_status ), array( 'ID' => $this->attachment_id ) );
			wp_set_post_tags( $id, $this->attachment_tags );
		}
	}






	/**
	 * {@internal Missing Short Description}}
	 *
	 * @since unknown
	 * @version    Release: 2.7.1
	 *
	 * @param unknown_type $q
	 * @return unknown
	 */
	function edit_media_query( $q = false ) {
		if ( false === $q )
			$q = $_GET;
	
		$q['m']   = isset( $q['m'] ) ? (int) $q['m'] : 0;
		$q['cat'] = isset( $q['cat'] ) ? (int) $q['cat'] : 0;
		$q['post_type'] = 'media';
		$q['post_status'] = 'any';
		$q['posts_per_page'] = 15;
		$post_mime_types = array(	//	array( adj, noun )
					'image' => array(__('Images'), __('Manage Images'), __ngettext_noop('Image <span class="count">(%s)</span>', 'Images <span class="count">(%s)</span>')),
					'audio' => array(__('Audio'), __('Manage Audio'), __ngettext_noop('Audio <span class="count">(%s)</span>', 'Audio <span class="count">(%s)</span>')),
					'video' => array(__('Video'), __('Manage Video'), __ngettext_noop('Video <span class="count">(%s)</span>', 'Video <span class="count">(%s)</span>')),
				);
		$post_mime_types = apply_filters('post_mime_types', $post_mime_types);
	
		$avail_post_mime_types = get_available_post_mime_types('attachment');
	
		if ( isset($q['post_mime_type']) && !array_intersect( (array) $q['post_mime_type'], array_keys($post_mime_types) ) )
			unset($q['post_mime_type']);
	
		wp($q);
	
		return array($post_mime_types, $avail_post_mime_types);
	}
	
	/**
	 * Count number of attachments for the mime type(s).
	 *
	 * If you set the optional mime_type parameter, then an array will still be
	 * returned, but will only have the item you are looking for. It does not give
	 * you the number of attachments that are children of a post. You can get that
	 * by counting the number of children that post has.
	 *
	 * @since 2.5.0
	 * @version    Release: 2.7.1
	 *
	 * @param string|array $mime_type Optional. Array or comma-separated list of MIME patterns.
	 * @return array Number of posts for each mime type.
	 */
	function count_media( $mime_type = '' ) {
		global $wpdb;
	
		$and = wp_post_mime_type_where( $mime_type );
		$count = $wpdb->get_results( "SELECT post_mime_type, COUNT( * ) AS num_posts FROM $wpdb->posts WHERE post_type = 'media' $and GROUP BY post_mime_type", ARRAY_A );
	
		$stats = array( );
		foreach( (array) $count as $row ) {
			$stats[$row['post_mime_type']] = $row['num_posts'];
		}
	
		return (object) $stats;
	}





}

class LMBBox_MediaPress_Walker_Category_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth, $args) {
		extract($args);

		$output .= "\n<li id='category-$category->term_id'>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . $name . '" id="in-category-' . $category->term_id . '"' . (in_array( $category->term_id, $selected_cats ) ? ' checked="checked"' : "" ) . '/> ' . wp_specialchars( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}


$LMBBox_MediaPress = new LMBBox_MediaPress_Core( 'LMB^Box MediaPress', '0.1', 'lmbbox-mediapress', 'lmbbox-mediapress.php' );

?>