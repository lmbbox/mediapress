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
		
	}

	// Setup Hooks
	function _setup_hooks() {
		$this->_add_hook( 'filter', 'attachment_fields_to_edit', array( &$this, 'add_fields_to_edit' ), 11, 2 );
		$this->_add_hook( 'filter', 'attachment_fields_to_save', array( &$this, 'add_fields_to_save' ), 11, 2 );
	}



	function add_fields_to_edit( $form_fields, $post ) {
	
		$form_fields['post_type'] = array(
			'label' => __( 'Post Type' ),
			'value' => '',
			'input' => 'select',
			'select' => '<select id="attachments[' . $post->ID . '][post_type]" name="attachments[' . $post->ID . '][post_type]"><option value="attachment">' . __( 'Attachment' ) . '</option><option value="media">' . __( 'Media' ) . '</option></select>',
			'helps' => ''
		);
		
		$form_fields['post_status'] = array(
			'label' => __( 'Post Status' ),
			'value' => '',
			'input' => 'select',
			'select' => '<select id="attachments[' . $post->ID . '][post_status]" name="attachments[' . $post->ID . '][post_status]"><option value="inherit">' . __( 'Inherit' ) . '</option><option value="publish">' . __( 'Published' ) . '</option></select>',
			'helps' => __( 'Set to Post for Media Posting.' )
		);
		
		return $form_fields;
	}




/*
function custom_thumbnail_fields_to_edit( $form_fields, $post ) {

  if ( substr($post->post_mime_type, 0, 5) == 'image' ) {

    $attachments = get_children( array(
      'post_type' => 'attachment',
      'order' => 'ASC',
      'orderby' => 'post_title',
      'exclude' => $post->ID
    ) );
    $name = "attachments[$post->ID][post_parent]";
    $html = "<select id='$name' name='$name'>";
    $html .= "<option value='0'></option>";
    foreach ( $attachments as $attachment ) {
      $parent = get_post($attachment->post_parent);
      if ( $parent && $parent->post_type == 'attachment' ) { continue; }
      if ( $attachment->ID == $post->post_parent ) {
        $selected = " selected='selected'";
      } else {
        $selected = '';
      }
      $html .= "<option value='$attachment->ID'$selected>";
      $option_text = array( $attachment->post_title, ' ('.basename($attachment->guid).')');
      if ( strlen($option_text[1]) > 62 ) {
        $option_text[0] = '...';
      } elseif ( strlen($option_text[0].$option_text[1]) > 65 ) {
        $option_text[0] = substr($option_text[0], 0, 62-strlen($option_text[1])).'...';
      }
      $html .= $option_text[0].$option_text[1];
      $html .= "</option>";
    }
    $html .= "</select>";
    $form_fields['post_parent'] = array(
      'label' => __("Parent Item"),
      'value' => '',
      'input' => 'select',
      'select' => $html,
      'helps' => __("If this is a thumbnail for another item, choose that item here.")
    );

  } else {
		$form_fields['align'] = array(
			'label' => __('Alignment'),
			'input' => 'html',
			'html'  => "
				<input type='radio' name='attachments[$post->ID][align]' id='image-align-none-$post->ID' value='none' checked='checked' />
				<label for='image-align-none-$post->ID' class='align image-align-none-label'>" . __('None') . "</label>
				<input type='radio' name='attachments[$post->ID][align]' id='image-align-left-$post->ID' value='left' />
				<label for='image-align-left-$post->ID' class='align image-align-left-label'>" . __('Left') . "</label>
				<input type='radio' name='attachments[$post->ID][align]' id='image-align-center-$post->ID' value='center' />
				<label for='image-align-center-$post->ID' class='align image-align-center-label'>" . __('Center') . "</label>
				<input type='radio' name='attachments[$post->ID][align]' id='image-align-right-$post->ID' value='right' />
				<label for='image-align-right-$post->ID' class='align image-align-right-label'>" . __('Right') . "</label>\n",
		);
		$thumb = custom_thumbnail_image_downsize(false, $post->ID, 'thumbnail');
		$form_fields['image-size'] = array(
			'label' => __('Display'),
			'input' => 'html',
			'html'  => "
				<input type='radio' name='attachments[$post->ID][media-display]' id='media-display-text-$post->ID' value='text' checked='checked' />
				<label for='media-display-text-$post->ID'>" . __('Text') . "</label>" . ( $thumb ? "<input type='radio' name='attachments[$post->ID][media-display]' id='media-display-thumb-$post->ID' value='thumbnail' />
				<label for='media-display-thumb-$post->ID'>" . __('Thumbnail') . "</label>
				" : '' ) . "<input type='radio' name='attachments[$post->ID][media-display]' id='media-display-icon-$post->ID' value='icon' />
				<label for='media-display-icon-$post->ID'>" . __('Icon') . "</label>",
		);
  }

  return $form_fields;
}
*/


	function add_fields_to_save( $post, $attachment ) {
		if ($attachment['post_type'] == 'media') {
			$post['post_type'] = 'media';
			$post['post_status'] = $attachment['post_status'];
		}
		return $post;
	}


/*
function custom_thumbnail_attachment_fields_to_save($post, $attachment) {
  if ( isset($attachment['post_parent']) ) {
    if ( $attachment['post_parent'] != $post['post_parent'] ) {
      if ( $attachment['post_parent'] != 0 ) {
        $children = get_children( array(
          'post_parent' => $attachment['post_parent'],
          'post_type' => 'attachment',
          'post_mime_type' => 'image'
        ) );
        if ( $children ) {
          $old_attachment = current($children);
          wp_update_post( array(
            'ID' => $old_attachment->ID,
            'post_parent' => 0
          ) );
        }
      } else {
        $parent = get_post( $post['post_parent'] );
        if ( $parent->post_type != 'attachment' ) {
          return $post;
        }
      }
      $post['post_parent'] = $attachment['post_parent'];
    }
  }
	return $post;
}
*/


}


$LMBBox_MediaPress = new LMBBox_MediaPress_Core( 'LMB^Box MediaPress', '0.1', 'lmbbox-mediapress', 'lmbbox-mediapress.php' );

?>