<?php
/*
Plugin Name: Gallery Plugin
Plugin URI:  http://bestwebsoft.com/plugin/
Description: This plugin allows you to implement gallery page into web site.
Author: BestWebSoft
Version: 1.01
Author URI: http://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2011  BestWebSoft  ( admin@bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
$gllr_boxes = array (
	'Upload-File' => array (
		array( '_gllr_uploadedFile', '', '', '', '' ),
		)
);

if( ! function_exists( 'gllr_plugin_install' ) ) {
	function gllr_plugin_install() {
		if ( ! copy(WP_PLUGIN_DIR .'/gallery-plugin/themplate/gallery-template.php', TEMPLATEPATH .'/gallery-template.php'))
		{
			add_action( 'admin_notices', create_function( '',  'Error copy template file' ) );
		}
		if ( ! copy(WP_PLUGIN_DIR .'/gallery-plugin/themplate/gallery-single-template.php', TEMPLATEPATH .'/gallery-single-template.php'))
		{
			add_action( 'admin_notices', create_function( '',  'Error copy template file' ) );
		}
	}
}

if( ! function_exists( 'gllr_plugin_uninstall' ) ) {
	function gllr_plugin_uninstall() {
		if ( ! unlink(TEMPLATEPATH .'/gallery-template.php'))
		{
			add_action( 'admin_notices', create_function( '', 'Error delete template file' ) );
		}
		if ( ! unlink(TEMPLATEPATH .'/gallery-single-template.php'))
		{
			add_action( 'admin_notices', create_function( '', 'Error delete template file' ) );
		}
	}
}

if( ! function_exists( 'gllr_plugin_header' ) ) {
	function gllr_plugin_header() {
		global $post_type;
		?>
		<style>
		#adminmenu #menu-posts-gallery div.wp-menu-image
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_16.png") no-repeat scroll center center transparent;
		}
		#adminmenu #menu-posts-gallery:hover div.wp-menu-image, #adminmenu #menu-posts-gallery.wp-has-current-submenu div.wp-menu-image
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_16_c.png") no-repeat scroll center center transparent;
		}	
		.wrap #icon-edit.icon32-posts-gallery
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_36.png") no-repeat scroll left top transparent;
		}
		</style>
		<?php
	}
}

// Create post type for Gallery
if( ! function_exists( 'post_type_images' ) ) {
	function post_type_images() {
		register_post_type('gallery', array(
			'labels' => array(
				'name' => __( 'Galleries', 'gallery'),
				'singular_name' => __( 'Gallery', 'gallery'),
				'add_new' => __( 'Add New', 'gallery'),
				'add_new_item' => __( 'Add New Gallery', 'gallery'),
				'edit_item' => __( 'Edit Gallery', 'gallery'),
				'new_item' => __( 'New Gallery', 'gallery'),
				'view_item' => __( 'View Gallery', 'gallery'),
				'search_items' => __( 'Search Galleries', 'gallery'),
				'not_found' =>	__( 'No Galleries found', 'gallery'),
				'parent_item_colon' => '',
				'menu_name' => __( 'Galleries', 'gallery')
			),
			'public' => true,
			'publicly_queryable' => true,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => true,
			'supports' => array('title', 'editor'),
			'register_meta_box_cb' => 'init_metaboxes_gallery'
		));

		wp_enqueue_style( 'gllrStylesheet', WP_PLUGIN_URL .'/gallery-plugin/css/stylesheet.css' );
		wp_enqueue_style( 'gllrPrettyPhotoStylesheet', WP_PLUGIN_URL .'/gallery-plugin/pretty_photo/css/prettyPhoto.css' );
		//wp_enqueue_script( 'gllrPrettyPhotoJq', WP_PLUGIN_URL .'/gallery-plugin/pretty_photo/jquery-1.6.2.min.js', array( 'jquery' ) ); 
		wp_enqueue_script( 'gllrPrettyPhotoJs', WP_PLUGIN_URL .'/gallery-plugin/pretty_photo/js/jquery.prettyPhoto.js', array( 'jquery' ) ); 
	}
}

if( ! function_exists( 'addImageAncestorToMenu' ) ) {
	function addImageAncestorToMenu( $classes ) {
		if ( is_singular( 'gallery' ) ) {
			global $wpdb, $post;
			
			if ( empty( $post->ancestors ) ) {
				return $classes;
			}
			
			$menuQuery = "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '_menu_item_object_id' AND meta_value IN (" . implode(',', $post->ancestors) . ")";
			$menuItems = $wpdb->get_col( $menuQuery );
			
			if ( is_array( $menuItems ) ) {
				foreach ( $menuItems as $menuItem ) {
					if ( in_array( 'menu-item-' . $menuItem, $classes ) ) {
						$classes[] = 'current-page-ancestor';
					}
				}
			}
		}

		return $classes;
	}
}

function init_metaboxes_gallery() {
		add_meta_box( 'Upload-File', __( 'Upload File', 'gallery' ), 'gllr_post_custom_box', 'gallery', 'normal', 'high' ); 
		wp_enqueue_script( 'jquery_min', WP_PLUGIN_URL .'/gallery-plugin/upload/jquery.js', array( 'jquery' ) );
		wp_enqueue_script( 'jquery_upload', WP_PLUGIN_URL .'/gallery-plugin/upload/fileuploader.js', array( 'jquery' ) );
		wp_enqueue_style( 'jquery_css', WP_PLUGIN_URL .'/gallery-plugin/upload/fileuploader.css' );
}

// Create custom meta box for portfolio post type
if ( ! function_exists( 'gllr_post_custom_box' ) ) {
	function gllr_post_custom_box( $obj = '', $box = '' ) {
		global $post;
		
		$post_types = get_post_types( array( '_builtin' => false ) );
		?>
		<div style="padding-top:10px;"><label for="uploadscreen"><?php echo __( 'Choose a screenshot to upload:', 'gallery' ); ?></label>
			<input name="MAX_FILE_SIZE" value="1048576" type="hidden" />
			<div id="file-uploader-demo1" style="padding-top:10px;">		
				<noscript>			
					<p><?php echo __( 'Please enable JavaScript to use the file uploader.', 'gallery' ); ?></p>
				</noscript>         
			</div>
			<ul id="files" ></ul>
			<div id="hidden"></div>
			<div style="clear:both;"></div></div>
		<script type="text/javascript">
		jQuery(document).ready(function()
		{
				var uploader = new qq.FileUploader({
						element: document.getElementById('file-uploader-demo1'),
						action: '<?php echo WP_PLUGIN_URL; ?>/gallery-plugin/upload/php.php',
						debug: false,
						onComplete: function(id, fileName, result) {
							if(result.error) {
								//
							}
							else {
								jQuery('<li></li>').appendTo('#files').html('<img src="<?php echo WP_PLUGIN_URL; ?>/gallery-plugin/upload/files/'+fileName+'" alt="" /><div style="width:200px">'+fileName+'<br />' +result.width+'x'+result.height+'</div>').addClass('success');
								jQuery('<input type="hidden" name="undefined[]" id="undefined" value="'+fileName+'" />').appendTo('#hidden');
							}
						}
				});           
				jQuery('#images_albumdiv').remove();

		});

			function img_delete(id) {
				jQuery('#'+id).hide();
				jQuery('#delete_images').append('<input type="hidden" name="delete_images[]" value="'+id+'" />');
			}
		</script>
		<?php

		$posts = get_posts(array(
			"showposts"			=> -1,
			"what_to_show"	=> "posts",
			"post_status"		=> "inherit",
			"post_type"			=> "attachment",
			"orderby"				=> "menu_order ASC, ID ASC",
			"post_mime_type"=> "image/jpeg,image/gif,image/jpg,image/png",
			"post_parent"		=> $post->ID));
		echo '<ul class="gallery clearfix">';
    foreach ( $posts as $page ):
			echo '<li id="'.$page->ID.'">';
				$image_attributes = wp_get_attachment_image_src( $page->ID, 'thumbnail' );
				echo '<img src="'.$image_attributes[0].'" alt="'.$page->post_title.'" title="'.$page->post_title.'"/>';
				echo '<div class="delete"><a href="javascript:void(0);" onclick="img_delete('.$page->ID.');">Delete</a><div/>';
			echo '</li>';
    endforeach;
		echo '</ul><div style="clear:both;"></div>';
		echo '<div id="delete_images"></div>';		 
	}
}

// Use nonce for verification ...
if( ! function_exists ( 'echo_gllr_nonce' ) ) {
	function echo_gllr_nonce () {
		echo sprintf(
			'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
			'gllr_nonce_name',
			wp_create_nonce( plugin_basename(__FILE__) )
		);
	}
}

if ( ! function_exists ( 'gllr_save_postdata' ) ) {
	function gllr_save_postdata( $post_id, $post ) {
		global $post;
		global $wpdb;
		if( isset( $_REQUEST['undefined'] ) && ! empty( $_REQUEST['undefined'] ) ) {
			$array_file_name = $_REQUEST['undefined'];
			$uploadFile = array();
			$newthumb = array();

			$uploadDir =  wp_upload_dir( );

			while( list( $key, $val ) = each( $array_file_name ) ) {
				$imagename = $val;
				$uploadFile[] = $uploadDir["path"] ."/" . $imagename;
			}
			reset( $array_file_name );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$i = 0;
			while( list( $key, $val ) = each( $array_file_name ) ) {
				$file_name = $val;
				if ( copy ( ABSPATH ."wp-content/plugins/gallery-plugin/upload/files/".$file_name, $uploadFile[$i] ) ) {
					unlink( ABSPATH ."wp-content/plugins/gallery-plugin/upload/files/".$file_name );
					$overrides = array('test_form' => false );
				
					$file			= str_replace( "\\", "/",$uploadDir["path"] ) ."/" .$file_name;
					$filename = basename( $file );
					
					$wp_filetype	= wp_check_filetype( $filename, null );
					$attachment		= array(
						 'post_mime_type' => $wp_filetype['type'],
						 'post_title' => $filename,
						 'post_content' => '',
						 'post_status' => 'inherit'
					);
					$attach_id = wp_insert_attachment( $attachment, $file );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );			
					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_parent = %d WHERE ID = %d", $post->ID, $attach_id ) );
				}
				$i++;			
			}
		}
		if( isset( $_REQUEST['delete_images'] ) ) {
			foreach( $_REQUEST['delete_images'] as $delete_id ) {
				wp_delete_attachment( $delete_id );
			}
		}
	}
}

if ( ! function_exists ( 'gllr_admin_head' ) ) {
	function gllr_admin_head() {
		 echo '<link rel="stylesheet" type="text/css" href="'.plugins_url( 'css/stylesheet.css', __FILE__ ).'">';
	}
}

if ( ! function_exists ( 'gllr_plugin_init' ) ) {
	function gllr_plugin_init() {
	// Internationalization, first(!)
	load_plugin_textdomain( 'gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

	// Other init stuff, be sure to it after load_plugins_textdomain if it involves translated text(!)
	}
}

if( ! function_exists( 'gllr_custom_permalinks' ) ) {
	function gllr_custom_permalinks() {
		global $wp_rewrite;
		global $wpdb;
		$parent = $wpdb->get_var("SELECT $wpdb->posts.post_name FROM $wpdb->posts, $wpdb->postmeta WHERE meta_key = '_wp_page_template' AND meta_value = 'gallery-template.php' AND (post_status = 'publish' OR post_status = 'private') AND $wpdb->posts.ID = $wpdb->postmeta.post_id");	
		if( ! empty( $parent ) ) {
			$wp_rewrite->add_rule( '(.+)/'.$parent.'/([^/]+)/?$', 'index.php?post_type=gallery&title=$matches[2]&posts_per_page=-1', 'top' );
			$wp_rewrite->add_rule( ''.$parent.'/([^/]+)/?$', 'index.php?post_type=gallery&title=$matches[1]&posts_per_page=-1', 'top' );
			$wp_rewrite->add_rule( '(.+)/'.$parent.'/page/([^/]+)/?$', 'index.php?pagename='.$parent.'&paged=$matches[2]', 'top' );
			$wp_rewrite->add_rule( ''.$parent.'/page/([^/]+)/?$', 'index.php?pagename='.$parent.'&paged=$matches[1]', 'top' );
		}

		$wp_rewrite->flush_rules();
	}
}

if ( ! function_exists( 'gllr_template_redirect' ) ) {
	function gllr_template_redirect() 
	{ 
		global $wp_query, $post, $posts;
		if( 'gallery' == get_post_type() && "" == $wp_query->query_vars["s"] ) {
			$category = get_term_by( 'slug', $wp_query->query_vars["taxonomy"], 'images_album'); 
			if( 'private' == get_post_meta( $category->term_id, '_gllr_public_status', true ) && ! is_user_logged_in()) {
				@header( "Location: ".get_bloginfo( 'url' ) );
				exit();
			}
			include( TEMPLATEPATH . '/gallery-single-template.php' );
			exit(); 
		}
	}
}


// Change the columns for the edit CPT screen
if ( ! function_exists( 'gllr_change_columns' ) ) {
	function gllr_change_columns( $cols ) {
		$cols = array(
			'cb'				=> '<input type="checkbox" />',
			'title'			=> __('Title', 'gallery'),
			'autor'			=> __('Author', 'gallery'),
			'gallery'			=> __('Foto\'s', 'gallery'),
			'status'		=> __('Public', 'gallery'),
			'dates'			=> __('Date', 'gallery')
		);
		return $cols;
	}
}

if ( ! function_exists( 'gllr_custom_columns' ) ) {
	function gllr_custom_columns( $column, $post_id ) {
		global $wpdb;
		$post = get_post( $post_id );	
		$row = $wpdb->get_results( "SELECT *
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_parent = $post_id
				AND $wpdb->posts.post_type = 'attachment'
				AND (
				$wpdb->posts.post_status = 'inherit'
				)
				ORDER BY $wpdb->posts.post_title ASC" );
		switch ( $column ) {
		 //case "category":
			case "autor":
				$author_id=$post->post_author;
				echo '<a href="edit.php?post_type=post&amp;author='.$author_id.'">'.get_the_author_meta( 'user_nicename' , $author_id ).'</a>';
				break;
			case "gallery":
				echo count($row);
				break;
			case "status":
				if(	$post->post_status == 'publish' )
					echo '<a href="javascript:void(0)">Yes</a>';
				else
					echo '<a href="javascript:void(0)">No</a>';
				break;
			case "dates":
				echo strtolower( __( date( "F", strtotime( $post->post_date ) ), 'kerksite' ) )." ".date( "j Y", strtotime( $post->post_date ) );				
				break;
		}
		$wp_query =  $old_query;
	}
}

if ( ! function_exists( 'get_ID_by_slug' ) ) {
	function get_ID_by_slug($page_slug) {
			$page = get_page_by_path($page_slug);
			if ($page) {
					return $page->ID;
			} else {
					return null;
			}
	}
}

if ( ! function_exists( 'getPostsAvailableToRegisteredUsers' ) ) {
	function getPostsAvailableToRegisteredUsers()
	{
		$theme_location = 'menu_2';
		$theme_locations = get_nav_menu_locations();
		if( ! isset( $theme_locations[$theme_location] ) ) return false;
	 
		$menu_obj = get_term( $theme_locations[$theme_location], 'nav_menu' );
		if( ! $menu_obj ) 
			$menu_obj = false;

		global $wpdb;
		
		// Menu posts
		$menuPosts = $wpdb->get_col("
			SELECT
				pm.meta_value
			FROM
				$wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
				INNER JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				INNER JOIN $wpdb->postmeta pm ON tr.object_id = pm.post_id
			WHERE
				t.slug = '".$menu_obj->slug."' AND
				pm.meta_key = '_menu_item_object_id'
		");
			
		if (empty($menuPosts))
		{
			$menuPosts = array();
		}
		
		// All private posts
		$privatePosts = array();
		while (count($menuPosts))
		{
			$postId = array_shift($menuPosts);
			array_push($privatePosts, (int) $postId);
			
			$childPosts = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type IN ('document', 'page', 'image')", $postId));
			if (is_array($childPosts) && count($childPosts))
			{
				$menuPosts = array_merge($menuPosts, $childPosts);
			}
		}
		
		return array_unique($privatePosts);
	}
}

if ( function_exists( 'add_image_size' ) ) { 
	add_image_size( 'album-thumb', 120, 80, true ); //300 pixels wide (and unlimited height)
	add_image_size( 'foto-thumb', 160, 120, true ); //300 pixels wide (and unlimited height)
}

if( ! function_exists( 'gllr_plugin_header' ) ) {
	function gllr_plugin_header() {
		global $post_type;
		?>
		<style>		
		#adminmenu #menu-posts-gallery div.wp-menu-image
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_16.png") no-repeat scroll center center transparent;
		}
		#adminmenu #menu-posts-gallery:hover div.wp-menu-image, #adminmenu #menu-posts-gallery.wp-has-current-submenu div.wp-menu-image
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_16_c.png") no-repeat scroll center center transparent;
		}	
		<?php if ( $post_type == 'gallery' ) { ?>
		.wrap #icon-edit 
		{
			background: url("<?php echo get_bloginfo('url');?>/wp-content/plugins/gallery-plugin/images/icon_36.png") no-repeat scroll left top transparent;
		}
		<?php } ?>
		</style>
		<?php
	}
}

if( ! function_exists( 'the_excerpt_max_charlength' ) ) {
	function the_excerpt_max_charlength($charlength) {
		$excerpt = get_the_excerpt();
		$charlength++;
		if( strlen( $excerpt ) > $charlength ) {
			$subex = substr( $excerpt, 0, $charlength-5 );
			$exwords = explode( " ", $subex );
			$excut = - ( strlen ( $exwords [ count( $exwords ) - 1 ] ) );
			if( $excut < 0 ) {
				echo substr( $subex, 0, $excut );
			} 
			else {
				echo $subex;
			}
			echo "...";
		} 
		else {
			echo $excerpt;
		}
	}
}

if( ! function_exists( 'gllr_page_css_class' ) ) {
	function gllr_page_css_class( $classes, $item ) {
		$post_type = get_query_var( 'post_type' );
		global $wpdb;
		$parent_id = 0;
		if( $post_type == "gallery" ) {
			$parent_id = $wpdb->get_var("SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta WHERE meta_key = '_wp_page_template' AND meta_value = 'gallery-template.php' AND post_status = 'publish' AND $wpdb->posts.ID = $wpdb->postmeta.post_id");
			while ($parent_id) {
				$page = get_page($parent_id);
				if($page->post_parent > 0 )
					$parent_id  = $page->post_parent;
				else 
					break;
			}
			wp_reset_query();
		}
		if ($item->ID == $parent_id) {
        array_push($classes, 'current_page_item');
    }
    return $classes;
	}
}

register_activation_hook( __FILE__, 'gllr_plugin_install'); // activate plugin
register_deactivation_hook( __FILE__, 'gllr_plugin_uninstall'); // deactivate plugin

add_action( 'admin_head', 'gllr_plugin_header' );

add_action( 'init', 'gllr_plugin_init' );

add_action( 'init', 'post_type_images' ); // register post type
add_action( 'init', 'gllr_custom_permalinks' ); // add custom permalink for gallery

add_action( 'template_redirect', 'gllr_template_redirect' ); // add themplate for single gallery page

add_action( 'admin_head', 'gllr_admin_head' );
add_action( 'save_post', 'gllr_save_postdata', 1, 2 ); // save custom data from admin 

add_filter( 'nav_menu_css_class', 'addImageAncestorToMenu' );
add_filter( 'page_css_class', 'gllr_page_css_class', 10, 2 );

add_filter( 'manage_gallery_posts_columns', 'gllr_change_columns' );
add_action( 'manage_gallery_posts_custom_column', 'gllr_custom_columns', 10, 2 );

?>