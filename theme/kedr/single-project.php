<?php
/**
 * Страница проекта: галерея, описание, параметры, похожие работы.
 *
 * @package Kedr
 */

get_header();

while ( have_posts() ) :
	the_post();

	$kedr_id      = get_the_ID();
	$kedr_gallery = kedr_project_gallery( $kedr_id );
	$kedr_specs   = kedr_project_specs( $kedr_id );
	$kedr_price   = kedr_project_price( $kedr_id );
	$kedr_terms   = get_the_terms( $kedr_id, 'project_cat' );

	get_template_part(
		'template-parts/page-hero',
		null,
		[
			'eyebrow' => kedr_project_cats( $kedr_id ),
			'title'   => get_the_title(),
			'text'    => has_excerpt() ? get_the_excerpt() : '',
		]
	);
	?>
	<article class="container project">
		<div class="project__main">
			<?php if ( $kedr_gallery ) : ?>
				<div class="gallery">
					<?php foreach ( $kedr_gallery as $kedr_index => $kedr_image ) : ?>
						<?php
						// Во всю ширину: первое фото и непарное последнее (см. .gallery в main.css).
						$kedr_wide = 0 === $kedr_index || ( count( $kedr_gallery ) - 1 === $kedr_index && 1 === $kedr_index % 2 );
						?>
						<a class="gallery__item" href="<?php echo esc_url( wp_get_attachment_image_url( $kedr_image, 'full' ) ); ?>" data-lightbox="project">
							<?php
							echo wp_get_attachment_image(
								$kedr_image,
								$kedr_wide ? 'kedr-wide' : 'kedr-card',
								false,
								0 === $kedr_index ? [ 'loading' => 'eager', 'fetchpriority' => 'high' ] : []
							);
							?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="gallery"><?php echo kedr_placeholder( get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</div>

		<aside class="project__aside">
			<div class="card specs">
				<h2 class="specs__title"><?php esc_html_e( 'Параметры проекта', 'kedr' ); ?></h2>
				<?php if ( $kedr_specs ) : ?>
					<dl class="specs__list">
						<?php foreach ( $kedr_specs as $kedr_label => $kedr_value ) : ?>
							<div>
								<dt><?php echo esc_html( $kedr_label ); ?></dt>
								<dd><?php echo esc_html( $kedr_value ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
				<?php if ( $kedr_price ) : ?>
					<p class="specs__price">
						<span><?php esc_html_e( 'Стоимость проекта', 'kedr' ); ?></span>
						<strong>
							<?php
							/* translators: %s: цена */
							echo esc_html( sprintf( __( 'от %s', 'kedr' ), kedr_rub( $kedr_price ) ) );
							?>
						</strong>
					</p>
				<?php endif; ?>
				<a class="btn btn--accent btn--block" href="#lead"><?php esc_html_e( 'Хочу такой же', 'kedr' ); ?></a>
				<p class="specs__note"><?php esc_html_e( 'Цена зависит от размеров помещения и выбранных материалов. Рассчитаем вашу стоимость после бесплатного замера.', 'kedr' ); ?></p>
			</div>
		</aside>
	</article>
	<?php
	$kedr_related = new WP_Query(
		[
			'post_type'      => 'project',
			'posts_per_page' => 3,
			'post__not_in'   => [ $kedr_id ],
			'no_found_rows'  => true,
			'orderby'        => 'rand',
			'tax_query'      => $kedr_terms && ! is_wp_error( $kedr_terms ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				? [
					[
						'taxonomy' => 'project_cat',
						'terms'    => wp_list_pluck( $kedr_terms, 'term_id' ),
					],
				]
				: [],
		]
	);

	// В категории мало работ — добираем из остальных.
	if ( $kedr_related->post_count < 3 ) {
		$kedr_related = new WP_Query(
			[
				'post_type'      => 'project',
				'posts_per_page' => 3,
				'post__not_in'   => [ $kedr_id ],
				'no_found_rows'  => true,
				'orderby'        => 'rand',
			]
		);
	}

	if ( $kedr_related->have_posts() ) :
		?>
		<section class="section section--alt" aria-labelledby="related-title">
			<div class="container">
				<?php
				kedr_section_head(
					[
						'eyebrow'    => __( 'Ещё проекты', 'kedr' ),
						'title'      => __( 'Похожие работы', 'kedr' ),
						'id'         => 'related-title',
						'link'       => get_post_type_archive_link( 'project' ),
						'link_label' => __( 'Все работы', 'kedr' ),
					]
				);
				?>
				<div class="project-grid">
					<?php
					while ( $kedr_related->have_posts() ) {
						$kedr_related->the_post();
						get_template_part( 'template-parts/project-card' );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
