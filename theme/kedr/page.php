<?php
/**
 * Обычная страница.
 *
 * @package Kedr
 */

get_header();

while ( have_posts() ) :
	the_post();

	get_template_part(
		'template-parts/page-hero',
		null,
		[
			'title' => get_the_title(),
			'text'  => has_excerpt() ? get_the_excerpt() : '',
		]
	);
	?>
	<div class="section section--tight">
		<div class="container">
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
	<?php
endwhile;

get_footer();
