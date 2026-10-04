<?php
/**
 * Template Name: Контакты
 *
 * Карточки контактов из Customizer и текст страницы (как добраться и т. п.).
 *
 * @package Kedr
 */

get_header();

$kedr_phone   = kedr_opt( 'phone' );
$kedr_email   = sanitize_email( kedr_opt( 'email' ) );
$kedr_socials = kedr_socials();

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
	<section class="section section--tight">
		<div class="container">
			<div class="contact-grid">
				<div class="contact-card" data-reveal>
					<span class="contact-card__icon"><?php kedr_the_icon( 'phone' ); ?></span>
					<p class="contact-card__label"><?php esc_html_e( 'Телефон', 'kedr' ); ?></p>
					<a class="contact-card__value" href="<?php echo esc_url( kedr_phone_href( $kedr_phone ) ); ?>"><?php echo esc_html( $kedr_phone ); ?></a>
					<p class="contact-card__note"><?php echo esc_html( kedr_opt( 'hours' ) ); ?></p>
				</div>

				<?php if ( $kedr_email ) : ?>
					<div class="contact-card" data-reveal>
						<span class="contact-card__icon"><?php kedr_the_icon( 'mail' ); ?></span>
						<p class="contact-card__label"><?php esc_html_e( 'Почта', 'kedr' ); ?></p>
						<a class="contact-card__value" href="mailto:<?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<p class="contact-card__note"><?php esc_html_e( 'Пришлите план помещения или фото — оценим проект заочно', 'kedr' ); ?></p>
					</div>
				<?php endif; ?>

				<div class="contact-card" data-reveal>
					<span class="contact-card__icon"><?php kedr_the_icon( 'pin' ); ?></span>
					<p class="contact-card__label"><?php esc_html_e( 'Шоурум и производство', 'kedr' ); ?></p>
					<p class="contact-card__value"><?php echo esc_html( kedr_opt( 'address' ) ); ?></p>
					<p class="contact-card__note"><?php esc_html_e( 'Образцы материалов и фурнитуры можно посмотреть вживую', 'kedr' ); ?></p>
				</div>

				<?php if ( $kedr_socials ) : ?>
					<div class="contact-card" data-reveal>
						<span class="contact-card__icon"><?php kedr_the_icon( 'message' ); ?></span>
						<p class="contact-card__label"><?php esc_html_e( 'Мессенджеры', 'kedr' ); ?></p>
						<ul class="socials socials--light">
							<?php foreach ( $kedr_socials as $kedr_social ) : ?>
								<li><a href="<?php echo esc_url( $kedr_social['url'] ); ?>" rel="noopener"><?php echo esc_html( $kedr_social['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
						<p class="contact-card__note"><?php esc_html_e( 'Отвечаем с 9:00 до 21:00 без выходных', 'kedr' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( get_the_content() ) : ?>
				<div class="entry-content contacts__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;

get_footer();
