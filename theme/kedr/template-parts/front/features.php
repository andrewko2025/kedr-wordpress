<?php
/**
 * Преимущества мастерской.
 *
 * @package Kedr
 */

?>
<section class="section section--alt" aria-labelledby="features-title">
	<div class="container">
		<?php
		kedr_section_head(
			[
				'eyebrow' => __( 'Почему мы', 'kedr' ),
				'title'   => __( 'Делаем мебель, за которую не стыдно', 'kedr' ),
				'id'      => 'features-title',
			]
		);
		?>
		<div class="features">
			<?php foreach ( kedr_features() as $kedr_feature ) : ?>
				<div class="feature" data-reveal>
					<span class="feature__icon"><?php kedr_the_icon( $kedr_feature['icon'] ); ?></span>
					<h3><?php echo esc_html( $kedr_feature['title'] ); ?></h3>
					<p><?php echo esc_html( $kedr_feature['text'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
