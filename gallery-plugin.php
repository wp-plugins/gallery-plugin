<?php
/*
Plugin Name: Gallery Plugin
Plugin URI:  http://bestwebsoft.com/plugin/
Description: This plugin allows you to implement gallery page into web site.
Author: BestWebSoft
Version: 2.07
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
		if ( ! file_exists( TEMPLATEPATH .'/gallery-template.php' ) && ! copy(WP_PLUGIN_DIR .'/gallery-plugin/template/gallery-template.php', TEMPLATEPATH .'/gallery-template.php' ) ) {
			add_action( 'admin_notices', create_function( '',  'echo "Error copy template file";' ) );
		}
		if ( ! file_exists( TEMPLATEPATH .'/gallery-single-template.php' ) && ! copy(WP_PLUGIN_DIR .'/gallery-plugin/template/gallery-single-template.php', TEMPLATEPATH .'/gallery-single-template.php') ) {
			add_action( 'admin_notices', create_function( '',  'echo "Error copy template file";' ) );
		}
	}
}

if( ! function_exists( 'gllr_plugin_uninstall' ) ) {
	function gllr_plugin_uninstall() {
		if ( file_exists( TEMPLATEPATH .'/gallery-template.php' ) && ! unlink(TEMPLATEPATH .'/gallery-template.php') ) {
			add_action( 'admin_notices', create_function( '', ' return "Error delete template file";' ) );
		}
		if ( file_exists( TEMPLATEPATH .'/gallery-single-template.php' ) && ! unlink(TEMPLATEPATH .'/gallery-single-template.php') ) {
			add_action( 'admin_notices', create_function( '', ' return "Error delete template file";' ) );
		}
		if( get_option( 'gllr_options' ) ) {
			delete_option( 'gllr_options' );
		}
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
		wp_enqueue_script( 'gllrPrettyPhotoJs', WP_PLUGIN_URL .'/gallery-plugin/pretty_photo/js/jquery.prettyPhoto.js', array( 'jquery' ) ); 
		wp_enqueue_script( 'gllrFileuploaderJs', WP_PLUGIN_URL .'/gallery-plugin/upload/fileuploader.js', array( 'jquery' ) );
		wp_enqueue_style( 'gllrFileuploadercss', WP_PLUGIN_URL .'/gallery-plugin/upload/fileuploader.css' );
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
}

// Create custom meta box for portfolio post type
if ( ! function_exists( 'gllr_post_custom_box' ) ) {
	function gllr_post_custom_box( $obj = '', $box = '' ) {
		global $post;
		$key = "gllr_image_text";
		
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
			"post_parent"		=> $post->ID)); ?>
		<ul class="gallery clearfix">
		<?php foreach ( $posts as $page ):
			$image_text = get_post_meta( $page->ID, $key, FALSE );
			echo '<li id="'.$page->ID.'" class="gllr_image_block">';
				$image_attributes = wp_get_attachment_image_src( $page->ID, 'thumbnail' );
				echo '<img src="'.$image_attributes[0].'" alt="'.$page->post_title.'" title="'.$page->post_title.'"/>';
				echo '<input type="text" name="gllr_image_text['.$page->ID.']" value="'.get_post_meta( $page->ID, $key, TRUE ).'" class="gllr_image_text" />';
				echo '<div class="delete"><a href="javascript:void(0);" onclick="img_delete('.$page->ID.');">Delete</a><div/>';
			echo '</li>';
    endforeach; ?>
		</ul><div style="clear:both;"></div>
		<div id="delete_images"></div>	 
	<?php }
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
		$key = "gllr_image_text";
		if( isset( $_REQUEST['delete_images'] ) ) {
			foreach( $_REQUEST['delete_images'] as $delete_id ) {
				delete_post_meta( $delete_id, $key );
				wp_delete_attachment( $delete_id );
			}
		}
		if( isset( $_REQUEST['gllr_image_text'] ) ) {
			$posts = get_posts(array(
				"showposts"			=> -1,
				"what_to_show"	=> "posts",
				"post_status"		=> "inherit",
				"post_type"			=> "attachment",
				"orderby"				=> "menu_order ASC, ID ASC",
				"post_mime_type"=> "image/jpeg,image/gif,image/jpg,image/png",
				"post_parent"		=> $post->ID));
			foreach ( $posts as $page ) {
				if( isset( $_REQUEST['gllr_image_text'][$page->ID] ) ) {
					$value = $_REQUEST['gllr_image_text'][$page->ID];
					if( get_post_meta( $page->ID, $key, FALSE ) && $value ) {
						// Custom field has a value and this custom field exists in database
						update_post_meta( $page->ID, $key, $value );
					} 
					elseif($value) {
						// Custom field has a value, but this custom field does not exist in database
						add_post_meta( $page->ID, $key, $value );
					}
				}
			}
		}
	}
}

if ( ! function_exists ( 'gllr_plugin_init' ) ) {
	function gllr_plugin_init() {
	// Internationalization, first(!)
		load_plugin_textdomain( 'gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
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
			if( 'private' == get_post_meta( $category->term_id, '_gllr_public_status', true ) && ! is_user_logged_in() ) {
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
			'title'			=> __( 'Title', 'gallery' ),
			'autor'			=> __( 'Author', 'gallery' ),
			'gallery'			=> __( 'Photo\'s', 'gallery' ),
			'status'		=> __( 'Public', 'gallery' ),
			'dates'			=> __( 'Date', 'gallery' )
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

if( ! function_exists( 'bws_add_menu_render' ) ) {
	function bws_add_menu_render() {
		global $title;
		$active_plugins = get_option('active_plugins');
		$all_plugins		= get_plugins();

		$array_activate = array();
		$array_install	= array();
		$array_recomend = array();
		$count_activate = $count_install = $count_recomend = 0;
		$array_plugins	= array(
			array( 'captcha\/captcha.php', 'Captcha', 'http://wordpress.org/extend/plugins/captcha/', 'http://bestwebsoft.com/plugin/captcha-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Captcha+bestwebsoft&plugin-search-input=Search+Plugins', 'admin.php?page=captcha.php' ), 
			array( 'contact-form-plugin\/contact_form.php', 'Contact Form', 'http://wordpress.org/extend/plugins/contact-form-plugin/', 'http://bestwebsoft.com/plugin/contact-form/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Contact+Form+bestwebsoft&plugin-search-input=Search+Plugins', 'admin.php?page=contact_form.php' ), 
			array( 'facebook-button-plugin\/facebook-button-plugin.php', 'Facebook Like Button Plugin', 'http://wordpress.org/extend/plugins/facebook-button-plugin/', 'http://bestwebsoft.com/plugin/facebook-like-button-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Facebook+Like+Button+Plugin+bestwebsoft&plugin-search-input=Search+Plugins', 'admin.php?page=facebook-button-plugin.php' ), 
			array( 'twitter-plugin\/twitter.php', 'Twitter Plugin', 'http://wordpress.org/extend/plugins/twitter-plugin/', 'http://bestwebsoft.com/plugin/twitter-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Twitter+Plugin+bestwebsoft&plugin-search-input=Search+Plugins', 'admin.php?page=twitter.php' ), 
			array( 'portfolio\/portfolio.php', 'Portfolio', 'http://wordpress.org/extend/plugins/portfolio/', 'http://bestwebsoft.com/plugin/portfolio-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Portfolio+bestwebsoft&plugin-search-input=Search+Plugins', '' ),
			array( 'gallery-plugin\/gallery-plugin.php', 'Gallery', 'http://wordpress.org/extend/plugins/gallery-plugin/', 'http://bestwebsoft.com/plugin/gallery-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Gallery+Plugin+bestwebsoft&plugin-search-input=Search+Plugins', '' ),
			array( 'adsense-plugin\/adsense-plugin.php', 'Google AdSense Plugin', 'http://wordpress.org/extend/plugins/adsense-plugin/', 'http://bestwebsoft.com/plugin/google-adsense-plugin/', '/wp-admin/plugin-install.php?tab=search&type=term&s=Adsense+Plugin+bestwebsoft&plugin-search-input=Search+Plugins', 'admin.php?page=adsense-plugin.php' )
		);
		foreach($array_plugins as $plugins)
		{
			if( 0 < count( preg_grep( "/".$plugins[0]."/", $active_plugins ) ) )
			{
				$array_activate[$count_activate]['title'] = $plugins[1];
				$array_activate[$count_activate]['link']	= $plugins[2];
				$array_activate[$count_activate]['href']	= $plugins[3];
				$array_activate[$count_activate]['url']	= $plugins[5];
				$count_activate++;
			}
			else if( array_key_exists(str_replace("\\", "", $plugins[0]), $all_plugins) )
			{
				$array_install[$count_install]['title'] = $plugins[1];
				$array_install[$count_install]['link']	= $plugins[2];
				$array_install[$count_install]['href']	= $plugins[3];
				$count_install++;
			}
			else
			{
				$array_recomend[$count_recomend]['title'] = $plugins[1];
				$array_recomend[$count_recomend]['link']	= $plugins[2];
				$array_recomend[$count_recomend]['href']	= $plugins[3];
				$array_recomend[$count_recomend]['slug']	= $plugins[4];
				$count_recomend++;
			}
		}
		?>
		<div class="wrap">
			<div class="icon32 icon32-bws" id="icon-options-general"></div>
			<h2><?php echo $title;?></h2>
			<?php if( 0 < $count_activate ) { ?>
			<div>
				<h3><?php _e( 'Activated plugins', 'gallery' ); ?></h3>
				<?php foreach( $array_activate as $activate_plugin ) { ?>
				<div style="float:left; width:200px;"><?php echo $activate_plugin['title']; ?></div> <p><a href="<?php echo $activate_plugin['link']; ?>" target="_blank"><?php echo __( "Read more", 'gallery'); ?></a> <a href="<?php echo $activate_plugin['url']; ?>"><?php echo __( "Settings", 'gallery'); ?></a></p>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if( 0 < $count_install ) { ?>
			<div>
				<h3><?php _e( 'Installed plugins', 'gallery' ); ?></h3>
				<?php foreach($array_install as $install_plugin) { ?>
				<div style="float:left; width:200px;"><?php echo $install_plugin['title']; ?></div> <p><a href="<?php echo $install_plugin['link']; ?>" target="_blank"><?php echo __( "Read more", 'gallery'); ?></a></p>
				<?php } ?>
			</div>
			<?php } ?>
			<?php if( 0 < $count_recomend ) { ?>
			<div>
				<h3><?php _e( 'Recommended plugins', 'gallery' ); ?></h3>
				<?php foreach( $array_recomend as $recomend_plugin ) { ?>
				<div style="float:left; width:200px;"><?php echo $recomend_plugin['title']; ?></div> <p><a href="<?php echo $recomend_plugin['link']; ?>" target="_blank"><?php echo __( "Read more", 'gallery'); ?></a> <a href="<?php echo $recomend_plugin['href']; ?>" target="_blank"><?php echo __( "Download", 'gallery'); ?></a> <a class="install-now" href="<?php echo get_bloginfo( "url" ) . $recomend_plugin['slug']; ?>" title="<?php esc_attr( sprintf( __( 'Install %s' ), $recomend_plugin['title'] ) ) ?>" target="_blank"><?php echo __( 'Install now from wordpress.org', 'gallery' ) ?></a></p>
				<?php } ?>
				<span style="color: rgb(136, 136, 136); font-size: 10px;"><?php _e( 'If you have any questions, please contact us via plugin@bestwebsoft.com or fill in our contact form on our site', 'gallery' ); ?> <a href="http://bestwebsoft.com/contact/">http://bestwebsoft.com/contact/</a></span>
			</div>
			<?php } ?>
		</div>
		<?php
	}
}

if( ! function_exists( 'add_gllr_admin_menu' ) ) {
	function add_gllr_admin_menu() {
		add_menu_page( 'BWS Plugins', 'BWS Plugins', 'manage_options', 'bws_plugins', 'bws_add_menu_render', plugins_url("images/px.png", __FILE__), 1001); 
		add_submenu_page('bws_plugins', __( 'Gallery', 'gallery' ), __( 'Gallery', 'gallery' ), 'manage_options', "gallery-plugin.php", 'gllr_settings_page');

		//call register settings function
		add_action( 'admin_init', 'register_gllr_settings' );
	}
}

// register settings function
if( ! function_exists( 'register_gllr_settings' ) ) {
	function register_gllr_settings() {
		global $wpmu;
		global $gllr_options;

		$gllr_option_defaults = array(
			'gllr_custom_size_name' => array( 'album-thumb', 'photo-thumb' ),
			'gllr_custom_size_px' => array( array(120, 80), array(160, 120) ),
			'custom_image_row_count' => 3
		);

		// install the option defaults
		if ( 1 == $wpmu ) {
			if( !get_site_option( 'gllr_options' ) ) {
				add_site_option( 'gllr_options', $gllr_option_defaults, '', 'yes' );
			}
		} 
		else {
			if( !get_option( 'gllr_options' ) )
				add_option( 'gllr_options', $gllr_option_defaults, '', 'yes' );
		}

		// get options from the database
		if ( 1 == $wpmu )
		 $gllr_options = get_site_option( 'gllr_options' ); // get options from the database
		else
		 $gllr_options = get_option( 'gllr_options' );// get options from the database

		// array merge incase this version has added new options
		$gllr_options = array_merge( $gllr_option_defaults, $gllr_options );
		if ( function_exists( 'add_image_size' ) ) { 
			add_image_size( 'album-thumb', $gllr_options['gllr_custom_size_px'][0][0], $gllr_options['gllr_custom_size_px'][0][1], true );
			add_image_size( 'photo-thumb', $gllr_options['gllr_custom_size_px'][1][0], $gllr_options['gllr_custom_size_px'][1][1], true );
		}
	}
}

if( ! function_exists( 'gllr_settings_page' ) ) {
	function gllr_settings_page() {
		global $gllr_options;
		$error = "";
		
		// Save data for settings page
		if( isset( $_REQUEST['gllr_form_submit'] ) ) {
			$gllr_request_options = array();
			$gllr_request_options["gllr_custom_size_name"] = $gllr_options["gllr_custom_size_name"];

			$gllr_request_options["gllr_custom_size_px"] = array( 
				array( intval( trim( $_REQUEST['custom_image_size_w_album'] ) ), intval( trim($_REQUEST['custom_image_size_h_album'] ) ) ), 
				array( intval( trim( $_REQUEST['custom_image_size_w_photo'] ) ), intval( trim($_REQUEST['custom_image_size_h_photo'] ) ) ) 
			);
			$gllr_request_options["custom_image_row_count"] =  intval( trim( $_REQUEST['custom_image_row_count'] ) );
			if( $gllr_request_options["custom_image_row_count"] == "" || $gllr_request_options["custom_image_row_count"] < 1 )
				$gllr_request_options["custom_image_row_count"] = 1;

			// array merge incase this version has added new options
			$gllr_options = array_merge( $gllr_options, $gllr_request_options );

			// Check select one point in the blocks Arithmetic actions and Difficulty on settings page
			update_option( 'gllr_options', $gllr_options, '', 'yes' );
			$message = __( "Options saved.", 'gallery' );
		}

		// Display form on the setting page
	?>
	<div class="wrap">
		<div class="icon32 icon32-bws" id="icon-options-general"></div>
		<h2><?php _e('Gallery Options', 'gallery' ); ?></h2>
		<div class="updated fade" <?php if( ! isset( $_REQUEST['gllr_form_submit'] ) || $error != "" ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
		<div class="error" <?php if( "" == $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
		<form method="post" action="admin.php?page=gallery-plugin.php" id="gllr_form_image_size">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('The size of the cover album for gallery', 'gallery' ); ?> </th>
					<td>
						<label for="custom_image_size_name"><?php _e( 'Image size name', 'gallery' ); ?></label> <?php echo $gllr_options["gllr_custom_size_name"][0]; ?><br />
						<label for="custom_image_size_w"><?php _e( 'Width (in px)', 'gallery' ); ?></label> <input type="text" name="custom_image_size_w_album" value="<?php echo $gllr_options["gllr_custom_size_px"][0][0]; ?>" /><br />
						<label for="custom_image_size_h"><?php _e( 'Height (in px)', 'gallery' ); ?></label> <input type="text" name="custom_image_size_h_album" value="<?php echo $gllr_options["gllr_custom_size_px"][0][1]; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Size for gallery image', 'gallery' ); ?> </th>
					<td>
						<label for="custom_image_size_name"><?php _e( 'Image size name', 'gallery' ); ?></label> <?php echo $gllr_options["gllr_custom_size_name"][1]; ?><br />
						<label for="custom_image_size_w"><?php _e( 'Width (in px)', 'gallery' ); ?></label> <input type="text" name="custom_image_size_w_photo" value="<?php echo $gllr_options["gllr_custom_size_px"][1][0]; ?>" /><br />
						<label for="custom_image_size_h"><?php _e( 'Height (in px)', 'gallery' ); ?></label> <input type="text" name="custom_image_size_h_photo" value="<?php echo $gllr_options["gllr_custom_size_px"][1][1]; ?>" />
					</td>
				</tr>
				<tr valign="top">
					<td colspan="2"><span style="color: #888888;font-size: 10px;"><?php _e( 'WordPress will create a copy of the post thumbnail with the specified dimensions when you upload a new photo.', 'gallery' ); ?></span></th>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Count images in row', 'gallery' ); ?> </th>
					<td>
						<input type="text" name="custom_image_row_count" value="<?php echo $gllr_options["custom_image_row_count"]; ?>" />
					</td>
				</tr>
			</table>    
			<input type="hidden" name="gllr_form_submit" value="submit" />
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php } 
}

if( ! function_exists( 'gllr_register_plugin_links' ) ) {
	function gllr_register_plugin_links($links, $file) {
		$base = plugin_basename(__FILE__);
		if ($file == $base) {
			$links[] = '<a href="admin.php?page=gallery-plugin.php">' . __( 'Settings', 'gallery' ) . '</a>';
			$links[] = '<a href="http://wordpress.org/extend/plugins/gallery-plugin/faq/" target="_blank">' . __( 'FAQ', 'gallery' ) . '</a>';
			$links[] = '<a href="Mailto:plugin@bestwebsoft.com">' . __( 'Support', 'gallery' ) . '</a>';
		}
		return $links;
	}
}

if( ! function_exists( 'gllr_plugin_action_links' ) ) {
	function gllr_plugin_action_links( $links, $file ) {
			//Static so we don't call plugin_basename on every plugin row.
		static $this_plugin;
		if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

		if ( $file == $this_plugin ){
				 $settings_link = '<a href="admin.php?page=gallery-plugin.php">' . __( 'Settings', 'gallery' ) . '</a>';
				 array_unshift( $links, $settings_link );
			}
		return $links;
	} // end function gllr_plugin_action_links
}

register_activation_hook( __FILE__, 'gllr_plugin_install' ); // activate plugin
register_uninstall_hook( __FILE__, 'gllr_plugin_uninstall' ); // deactivate plugin

// adds "Settings" link to the plugin action page
add_filter( 'plugin_action_links', 'gllr_plugin_action_links', 10, 2 );
//Additional links on the plugin page
add_filter( 'plugin_row_meta', 'gllr_register_plugin_links', 10, 2 );

add_action( 'admin_menu', 'add_gllr_admin_menu' );
add_action( 'init', 'gllr_plugin_init' );

add_action( 'init', 'post_type_images' ); // register post type
add_action( 'init', 'gllr_custom_permalinks' ); // add custom permalink for gallery

add_action( 'template_redirect', 'gllr_template_redirect' ); // add themplate for single gallery page

add_action( 'save_post', 'gllr_save_postdata', 1, 2 ); // save custom data from admin 

add_filter( 'nav_menu_css_class', 'addImageAncestorToMenu' );
add_filter( 'page_css_class', 'gllr_page_css_class', 10, 2 );

add_filter( 'manage_gallery_posts_columns', 'gllr_change_columns' );
add_action( 'manage_gallery_posts_custom_column', 'gllr_custom_columns', 10, 2 );

?>