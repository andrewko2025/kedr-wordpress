<?php
/**
 * Подвал: форма заявки (на всех страницах, кроме 404), контакты, скрипты.
 *
 * @package Kedr
 */

$kedr_phone = kedr_opt( 'phone' );
$kedr_email = sanitize_email( kedr_opt( 'email' ) );
$kedr_cats  = kedr_categories( true );
?>
	<?php if ( ! is_404() ) : ?>
		<?php get_template_part( 'template-parts/lead-form' ); ?>
	<?php endif; ?>
</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="site-footer__about">
			<?php kedr_brand(); ?>
			<p><?php esc_html_e( 'Проектируем и изготавливаем корпусную мебель по индивидуальным размерам с 2014 года. Собственное производство и гарантия 5 лет.', 'kedr' ); ?></p>
			<?php $kedr_socials = kedr_socials(); ?>
			<?php if ( $kedr_socials ) : ?>
				<ul class="socials">
					<?php foreach ( $kedr_socials as $kedr_social ) : ?>
						<li><a href="<?php echo esc_url( $kedr_social['url'] ); ?>" rel="noopener"><?php echo esc_html( $kedr_social['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<nav class="site-footer__col" aria-labelledby="footer-nav-title">
			<h2 class="site-footer__title" id="footer-nav-title"><?php esc_html_e( 'Разделы', 'kedr' ); ?></h2>
			<?php
			wp_nav_menu(
				[
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'site-footer__list',
					'depth'          => 1,
					'fallback_cb'    => 'kedr_menu_fallback',
				]
			);
			?>
		</nav>

		<?php if ( $kedr_cats ) : ?>
			<div class="site-footer__col">
				<h2 class="site-footer__title"><?php esc_html_e( 'Что делаем', 'kedr' ); ?></h2>
				<ul class="site-footer__list">
					<?php foreach ( $kedr_cats as $kedr_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $kedr_cat ) ); ?>"><?php echo esc_html( $kedr_cat->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="site-footer__col">
			<h2 class="site-footer__title"><?php esc_html_e( 'Контакты', 'kedr' ); ?></h2>
			<ul class="site-footer__list site-footer__contacts">
				<li><a class="site-footer__phone" href="<?php echo esc_url( kedr_phone_href( $kedr_phone ) ); ?>"><?php echo esc_html( $kedr_phone ); ?></a></li>
				<?php if ( $kedr_email ) : ?>
					<li><a href="mailto:<?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- antispambot кодирует в HTML-сущности. ?>"><?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
				<?php endif; ?>
				<li><?php echo esc_html( kedr_opt( 'address' ) ); ?></li>
				<li><?php echo esc_html( kedr_opt( 'hours' ) ); ?></li>
			</ul>
		</div>
	</div>

	<div class="container site-footer__bottom">
		<p>
			&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> «<?php bloginfo( 'name' ); ?>».
			<?php esc_html_e( 'Демо-проект для портфолио: компания, контакты и отзывы вымышлены.', 'kedr' ); ?>
			<?php if ( kedr_opt( 'demo_link' ) ) : ?>
				<a class="site-footer__demo" href="<?php echo esc_url( kedr_opt( 'demo_link' ) ); ?>" rel="noopener"><?php esc_html_e( 'Код проекта и живая админка →', 'kedr' ); ?></a>
			<?php endif; ?>
		</p>
		<?php the_privacy_policy_link(); ?>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
