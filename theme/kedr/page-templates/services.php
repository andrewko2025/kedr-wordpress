<?php
/**
 * Template Name: Услуги и цены
 *
 * Прайс по категориям работ, калькулятор и частые вопросы.
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

	$kedr_terms = kedr_categories();
	?>
	<section class="section section--tight">
		<div class="container">
			<?php if ( $kedr_terms ) : ?>
				<div class="price-list">
					<?php foreach ( $kedr_terms as $kedr_term ) : ?>
						<article class="price-row" data-reveal>
							<div class="price-row__media">
								<?php echo kedr_term_image( $kedr_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="price-row__body">
								<h2 class="price-row__title"><?php echo esc_html( $kedr_term->name ); ?></h2>
								<?php if ( $kedr_term->description ) : ?>
									<p><?php echo esc_html( $kedr_term->description ); ?></p>
								<?php endif; ?>
							</div>
							<div class="price-row__aside">
								<?php $kedr_price = kedr_term_price_data( $kedr_term ); ?>
								<?php if ( $kedr_price['price'] ) : ?>
									<span class="price-row__price">
										<?php
										/* translators: %s: цена */
										echo esc_html( sprintf( __( 'от %s', 'kedr' ), kedr_rub( $kedr_price['price'] ) ) );
										?>
									</span>
									<?php if ( $kedr_price['unit'] ) : ?>
										<span class="price-row__unit"><?php echo esc_html( $kedr_price['unit'] ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
								<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( get_term_link( $kedr_term ) ); ?>">
									<?php echo esc_html( $kedr_term->count . ' ' . kedr_plural( $kedr_term->count, __( 'работа', 'kedr' ), __( 'работы', 'kedr' ), __( 'работ', 'kedr' ) ) ); ?>
									<?php kedr_the_icon( 'arrow-right' ); ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="entry-content services__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	get_template_part( 'template-parts/calculator' );
	get_template_part( 'template-parts/front/faq' );
endwhile;

get_footer();
