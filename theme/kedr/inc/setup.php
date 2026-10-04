<?php
/**
 * Базовая настройка темы.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'kedr', KEDR_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
		add_theme_support(
			'custom-logo',
			[
				'height'      => 80,
				'width'       => 240,
				'flex-height' => true,
				'flex-width'  => true,
			]
		);

		add_image_size( 'kedr-card', 720, 540, true );
		add_image_size( 'kedr-wide', 1600, 1000, true );
		add_image_size( 'kedr-tall', 960, 1100, true );

		register_nav_menus(
			[
				'primary' => __( 'Главное меню', 'kedr' ),
				'footer'  => __( 'Меню в подвале', 'kedr' ),
			]
		);

		// Отрывок у страниц выводится подзаголовком в шапке страницы.
		add_post_type_support( 'page', 'excerpt' );

		// Редактор блоков показывает текст шрифтами сайта.
		add_theme_support( 'editor-styles' );
		add_editor_style( [ KEDR_FONTS_URL, 'assets/css/editor.css' ] );
	}
);

add_filter( 'document_title_separator', fn () => '—' );
add_filter( 'excerpt_length', fn () => 26 );
add_filter( 'excerpt_more', fn () => '…' );

// Убираем из <head> то, что сайту мастерской не нужно.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

// Класс js на <html> до отрисовки: на него завязаны анимации появления блоков.
add_action(
	'wp_head',
	function () {
		echo "<script>document.documentElement.classList.add('js');</script>\n";
	},
	0
);

// Комментарии на сайте мастерской не используются — убираем их и из админки.
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_action( 'admin_menu', fn () => remove_menu_page( 'edit-comments.php' ) );
add_action( 'admin_bar_menu', fn ( WP_Admin_Bar $bar ) => $bar->remove_node( 'comments' ), 99 );

// Пункт «Работы» в меню остаётся активным на страницах проектов и категорий.
add_filter(
	'nav_menu_css_class',
	function ( array $classes, $item ) {
		$is_projects_item = 'post_type_archive' === $item->type && 'project' === $item->object;

		if ( $is_projects_item && ( is_singular( 'project' ) || is_tax( 'project_cat' ) ) ) {
			$classes[] = 'current-menu-item';
		}

		return $classes;
	},
	10,
	2
);

// Каталог работ: 9 карточек на страницу, порядок задаётся вручную.
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_post_type_archive( 'project' ) || $query->is_tax( 'project_cat' ) ) {
			$query->set( 'posts_per_page', 9 );
			$query->set(
				'orderby',
				[
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				]
			);
		}
	}
);

// Новые типы записей добавляют свои ЧПУ — обновляем правила при активации темы.
add_action( 'after_switch_theme', fn () => flush_rewrite_rules() );
