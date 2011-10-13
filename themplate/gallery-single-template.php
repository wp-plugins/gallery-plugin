<?php get_header(); ?>
	<?php 
		global $post;
		global $wpdb;
	?>
	<div id="container">
		<div role="main" id="content">
			<?php 
			global $post;
			$args = array(
				'post_type'					=> 'gallery',
				'post_status'				=> 'publish',
				'name'							=> substr(basename( $_SERVER['REQUEST_URI'] ), strpos( basename( $_SERVER['REQUEST_URI'] ), "=")),
				'posts_per_page'		=> 1
			);	
			$second_query = new WP_Query( $args ); 
			if ($second_query->have_posts()) : while ($second_query->have_posts()) : $second_query->the_post(); ?>
				<h1 class="home_page_title"><?php the_title(); ?></h1>
				<div class="gallery_box_single">
					<?php the_content(); ?>
				<?php endwhile; else: ?>
				<div class="gallery_box_single">
					<p class="not_found">Sorry - nothing to found.</p>
				<?php endif; ?>
				<?php 
				$posts = get_posts(array(
					"showposts"			=> -1,
					"what_to_show"	=> "posts",
					"post_status"		=> "inherit",
					"post_type"			=> "attachment",
					"orderby"				=> "menu_order ASC, ID ASC",
					"post_mime_type"=> "image/jpeg,image/gif,image/jpg,image/png",
					"post_parent"		=> $post->ID
				));
				if( count($posts) > 0 ) {
				?>
				<ul class="gallery clearfix">
					<?php foreach($posts as $attachment) { 
					$image_attributes = wp_get_attachment_image_src( $attachment->ID, 'foto-thumb' );
					$image_attributes_large = wp_get_attachment_image_src( $attachment->ID, 'large' );
					?>
					<li>
						<a rel="prettyPhoto[gallery]" href="<?php echo $image_attributes_large[0]; ?>">
							<img style="width:157px;height:116px;" alt="" title="" src="<?php echo $image_attributes[0]; ?>" />
						</a>
					</li>
					<?php } }  ?>
				</ul>
				<div class="clear"></div>
			</div>
		</div>
	</div>
	<?php get_sidebar(); ?>
	<script type="text/javascript">
		var $j = jQuery.noConflict();
		$j(document).ready(function(){
			$j("a[rel^='prettyPhoto']").prettyPhoto({theme: 'dark_square'}); 
		});
</script>
<?php get_footer(); ?>