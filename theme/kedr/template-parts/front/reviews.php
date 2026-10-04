<?php
/**
 * Отзывы клиентов.
 *
 * @package Kedr
 */

?>
<section class="section" aria-labelledby="reviews-title">
	<div class="container">
		<?php
		kedr_section_head(
			[
				'eyebrow' => __( 'Отзывы', 'kedr' ),
				'title'   => __( 'Что говорят клиенты', 'kedr' ),
				'id'      => 'reviews-title',
			]
		);
		?>
		<div class="reviews">
			<?php foreach ( kedr_reviews() as $kedr_review ) : ?>
				<figure class="review" data-reveal>
					<span class="review__stars" role="img" aria-label="<?php esc_attr_e( 'Оценка 5 из 5', 'kedr' ); ?>">
						<?php
						for ( $kedr_i = 0; $kedr_i < 5; $kedr_i++ ) {
							kedr_the_icon( 'star' );
						}
						?>
					</span>
					<blockquote><p><?php echo esc_html( $kedr_review['text'] ); ?></p></blockquote>
					<figcaption>
						<span class="review__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $kedr_review['name'], 0, 1 ) ); ?></span>
						<span>
							<span class="review__name"><?php echo esc_html( $kedr_review['name'] ); ?></span>
							<span class="review__meta"><?php echo esc_html( $kedr_review['meta'] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<p class="reviews__note"><?php esc_html_e( 'Отзывы в демо-версии сайта вымышлены.', 'kedr' ); ?></p>
	</div>
</section>
