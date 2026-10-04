<?php
/**
 * Главный экран: оффер, кнопки, цифры и фото.
 *
 * @package Kedr
 */

$kedr_hero_id = (int) get_theme_mod( 'hero_image', 0 );

// Фото не выбрано в Customizer — берём главное фото первой работы.
if ( ! $kedr_hero_id ) {
	$kedr_first   = get_posts(
		[
			'post_type'      => 'project',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
			'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		]
	);
	$kedr_hero_id = $kedr_first ? (int) get_post_thumbnail_id( $kedr_first[0] ) : 0;
}
?>
<section class="hero">
	<div class="container hero__inner">
		<div class="hero__content">
			<p class="eyebrow"><?php echo esc_html( kedr_opt( 'hero_eyebrow' ) ); ?></p>
			<h1 class="hero__title"><?php echo esc_html( kedr_opt( 'hero_title' ) ); ?></h1>
			<p class="hero__text"><?php echo esc_html( kedr_opt( 'hero_text' ) ); ?></p>

			<div class="hero__actions">
				<a class="btn btn--accent btn--lg" href="#calc">
					<?php kedr_the_icon( 'calculator' ); ?>
					<?php esc_html_e( 'Рассчитать стоимость', 'kedr' ); ?>
				</a>
				<a class="btn btn--ghost btn--lg" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>">
					<?php esc_html_e( 'Смотреть работы', 'kedr' ); ?>
				</a>
			</div>

			<dl class="hero__stats">
				<?php for ( $kedr_i = 1; $kedr_i <= 3; $kedr_i++ ) : ?>
					<div>
						<dt><?php echo esc_html( kedr_opt( "stat_{$kedr_i}_label" ) ); ?></dt>
						<dd><?php echo esc_html( kedr_opt( "stat_{$kedr_i}_value" ) ); ?></dd>
					</div>
				<?php endfor; ?>
			</dl>
		</div>

		<div class="hero__media">
			<?php
			if ( $kedr_hero_id ) {
				echo wp_get_attachment_image(
					$kedr_hero_id,
					'kedr-tall',
					false,
					[
						'class'         => 'hero__img',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'sizes'         => '(min-width: 900px) 46vw, 100vw',
					]
				);
			} else {
				echo kedr_placeholder(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<div class="hero__badge">
				<?php kedr_the_icon( 'shield' ); ?>
				<div>
					<strong><?php esc_html_e( 'Гарантия 5 лет', 'kedr' ); ?></strong>
					<span><?php esc_html_e( 'на корпус, фасады и монтаж', 'kedr' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</section>
