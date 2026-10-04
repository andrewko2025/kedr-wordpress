<?php
/**
 * Последние работы.
 *
 * @package Kedr
 */

$kedr_projects = new WP_Query(
	[
		'post_type'           => 'project',
		'posts_per_page'      => 6,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'orderby'             => [
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		],
	]
);

if ( ! $kedr_projects->have_posts() ) {
	return;
}
?>
<section class="section" aria-labelledby="projects-title">
	<div class="container">
		<?php
		kedr_section_head(
			[
				'eyebrow'    => __( 'Портфолио', 'kedr' ),
				'title'      => __( 'Последние работы', 'kedr' ),
				'text'       => __( 'Каждый проект — под конкретную квартиру и конкретных людей. Вот что получилось недавно.', 'kedr' ),
				'id'         => 'projects-title',
				'link'       => get_post_type_archive_link( 'project' ),
				'link_label' => __( 'Все работы', 'kedr' ),
			]
		);
		?>
		<div class="project-grid">
			<?php
			while ( $kedr_projects->have_posts() ) {
				$kedr_projects->the_post();
				get_template_part( 'template-parts/project-card' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
