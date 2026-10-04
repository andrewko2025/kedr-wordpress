<?php
/**
 * Категории мебели с ценой «от».
 *
 * @package Kedr
 */

$kedr_terms = kedr_categories();

if ( ! $kedr_terms ) {
	return;
}
?>
<section class="section" id="services" aria-labelledby="services-title">
	<div class="container">
		<?php
		kedr_section_head(
			[
				'eyebrow' => __( 'Что мы делаем', 'kedr' ),
				'title'   => __( 'Мебель для каждой комнаты', 'kedr' ),
				'text'    => __( 'Проектируем под ваши размеры, технику и привычки. Цены — за погонный метр в базовой комплектации.', 'kedr' ),
				'id'      => 'services-title',
			]
		);
		?>
		<div class="cat-grid">
			<?php foreach ( $kedr_terms as $kedr_term ) : ?>
				<a class="cat-card" href="<?php echo esc_url( get_term_link( $kedr_term ) ); ?>" data-reveal>
					<div class="cat-card__media">
						<?php echo kedr_term_image( $kedr_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div class="cat-card__body">
						<h3 class="cat-card__title"><?php echo esc_html( $kedr_term->name ); ?></h3>
						<?php if ( $kedr_term->description ) : ?>
							<p class="cat-card__text"><?php echo esc_html( $kedr_term->description ); ?></p>
						<?php endif; ?>
						<div class="cat-card__foot">
							<span class="cat-card__price"><?php echo esc_html( kedr_term_price( $kedr_term ) ); ?></span>
							<span class="cat-card__arrow"><?php kedr_the_icon( 'arrow-right' ); ?></span>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
