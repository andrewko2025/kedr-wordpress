<?php
/**
 * Шапка сайта.
 *
 * @package Kedr
 */

$kedr_phone = kedr_opt( 'phone' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти к содержимому', 'kedr' ); ?></a>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<?php kedr_brand(); ?>

		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'Главное меню', 'kedr' ); ?>" data-nav>
			<?php
			wp_nav_menu(
				[
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
					'fallback_cb'    => 'kedr_menu_fallback',
				]
			);
			?>
			<div class="site-nav__extra">
				<a class="site-nav__phone" href="<?php echo esc_url( kedr_phone_href( $kedr_phone ) ); ?>"><?php echo esc_html( $kedr_phone ); ?></a>
				<span class="site-nav__hours"><?php echo esc_html( kedr_opt( 'hours' ) ); ?></span>
				<a class="btn btn--accent btn--block" href="#lead"><?php esc_html_e( 'Заказать бесплатный замер', 'kedr' ); ?></a>
			</div>
		</nav>

		<div class="site-header__actions">
			<a class="site-header__phone" href="<?php echo esc_url( kedr_phone_href( $kedr_phone ) ); ?>">
				<?php kedr_the_icon( 'phone' ); ?>
				<span><?php echo esc_html( $kedr_phone ); ?></span>
			</a>
			<a class="btn btn--accent btn--sm site-header__cta" href="#lead"><?php esc_html_e( 'Заказать замер', 'kedr' ); ?></a>
			<button class="burger" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="<?php esc_attr_e( 'Открыть меню', 'kedr' ); ?>" data-burger>
				<span class="burger__lines"></span>
			</button>
		</div>
	</div>
</header>

<main id="main" class="site-main">
