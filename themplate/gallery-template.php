<?php
/*
Template Name: Gallery Template
*/
?>

<?php get_header(); ?>
	<div id="container">
		<div role="main" id="content">
			<h1 class="home_page_title"><?php the_title(); ?></h1>
			<div class="gallery_box">
				<ul>
				<?php 
					global $post;
					global $wpdb;
					global $wp_query;
					$paged = $wp_query->query_vars["paged"];
					$permalink = get_permalink();
					if( substr( $permalink, strlen( $permalink ) -1 ) != "/" )
					{
						if( strpos( $permalink, "?" ) !== false ) {
							$permalink = substr( $permalink, 0, strpos( $permalink, "?" ) -1 )."/";
						}
						else {
							$permalink .= "/";
						}
					}
					$count = 0;
					$args = array(
						'post_type'					=> 'gallery',
						'post_status'				=> 'publish',
						'orderby'						=> 'post_date',
						'posts_per_page'		=> -1
					);
					$second_query = new WP_Query( $args );
					$count_all_albums = count($second_query->posts);
					$per_page = 5;
					if( $paged != 0 )
						$start = $per_page * ($paged - 1);
					else
						$start = $per_page * $paged;
					if ($second_query->have_posts()) : while ($second_query->have_posts()) : $second_query->the_post();
						if( $count < $start ) {
							$count++;
							continue;
						}
						if( ( $count - $start ) > $per_page - 1 )
							break;

					$attachments = get_children( 'post_parent='.$post->ID.'&post_type=attachment&post_mime_type=image&numberposts=1' );
					$id = key($attachments);
					$image_attributes = wp_get_attachment_image_src( $id, 'album-thumb' );
					$count++;
				?>
					<li>
						<img style="width:120px;" alt="<?php echo $post->post_name; ?>" title="<?php echo $post->post_name; ?>" src="<?php echo $image_attributes[0]; ?>" />
						<div class="gallery_detail_box">
							<div><?php echo $post->post_title; ?></div>
							<div><?php echo the_excerpt_max_charlength(100); ?></div>
							<a href="<?php echo $permalink; echo basename( get_permalink( $post->ID ) ); ?>">See foto's &raquo;</a>
						</div>
						<div class="clear"></div>
					</li>
				<?php endwhile; endif; wp_reset_query(); ?>
				</ul>
				<?php
					if( $paged == 0 )
							$paged = 1;
					$pages = intval ( $count_all_albums/$per_page );
					if( $count_all_albums % $per_page > 0 )
						$pages +=1;
					$showitems = 5;  
					$range = 100;
					if( ! $pages ) {
						$pages = 1;
					}
					if( 1 != $pages ) {
						echo "</div><div class='clear'></div><div class='pagination'>";
						for ( $i = 1; $i <= $pages; $i++ ) {
							if ( 1 != $pages && ( !( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) ) {
								echo ( $paged == $i ) ? "<span class='current'>". $i ."</span>":"<a href='". get_pagenum_link($i) ."' class='inactive' >". $i ."</a>";
							}
						}

						echo "<div class='clear'></div></div>\n";
					} else {?>
						</div>
					<?php } ?>
		</div>
	</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>