<?php
/**
 * Карточка работы. Вызывается внутри цикла.
 *
 * @package Kedr
 */

$kedr_id    = get_the_ID();
$kedr_price = kedr_project_price( $kedr_id );
$kedr_tags  = array_filter( [ kedr_project_meta( $kedr_id, 'size' ), kedr_project_meta( $kedr_id, 'duration' ) ] );
?>
<article class="project-card" data-reveal>
	<div class="project-card__media">
		<?php echo kedr_project_image( $kedr_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="project-card__body">
		<p class="project-card__cat"><?php echo esc_html( kedr_project_cats( $kedr_id ) ); ?></p>
		<h3 class="project-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( $kedr_tags ) : ?>
			<ul class="project-card__meta">
				<?php foreach ( $kedr_tags as $kedr_tag ) : ?>
					<li><?php echo esc_html( $kedr_tag ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $kedr_price ) : ?>
			<p class="project-card__price">
				<?php
				/* translators: %s: цена */
				echo esc_html( sprintf( __( 'от %s', 'kedr' ), kedr_rub( $kedr_price ) ) );
				?>
			</p>
		<?php endif; ?>
	</div>
</article>
