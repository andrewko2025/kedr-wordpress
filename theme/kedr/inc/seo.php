<?php
/**
 * Базовое SEO: description, Open Graph и разметка организации schema.org.
 * Отключается, если установлен SEO-плагин.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Активен ли SEO-плагин, который сам выводит эти теги.
 */
function kedr_has_seo_plugin(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/**
 * Описание текущей страницы для meta description и og:description.
 */
function kedr_meta_description(): string {
	if ( is_front_page() ) {
		$text = kedr_opt( 'hero_text' );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '…' );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$text = wp_strip_all_tags( term_description() );
	} elseif ( is_post_type_archive( 'project' ) ) {
		$text = __( 'Фотографии и описания кухонь, шкафов и гардеробных, которые мы изготовили для клиентов.', 'kedr' );
	} else {
		$text = get_bloginfo( 'description' );
	}

	return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
}

/**
 * Картинка для соцсетей: фото записи или главного экрана.
 */
function kedr_meta_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		return (string) get_the_post_thumbnail_url( null, 'kedr-wide' );
	}

	$hero = (int) get_theme_mod( 'hero_image', 0 );

	return $hero ? (string) wp_get_attachment_image_url( $hero, 'kedr-wide' ) : '';
}

add_action(
	'wp_head',
	function () {
		if ( kedr_has_seo_plugin() || is_404() ) {
			return;
		}

		global $wp;

		$description = kedr_meta_description();
		$image       = kedr_meta_image();
		$url         = is_singular() ? get_permalink() : home_url( $wp->request ? trailingslashit( $wp->request ) : '/' );

		$tags = [
			'og:type'      => is_singular( [ 'post', 'project' ] ) ? 'article' : 'website',
			'og:site_name' => get_bloginfo( 'name' ),
			'og:locale'    => get_locale(),
			'og:title'     => wp_get_document_title(),
			'og:url'       => $url,
		];

		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
			$tags['og:description'] = $description;
		}

		if ( $image ) {
			$tags['og:image'] = $image;
		}

		foreach ( $tags as $property => $content ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $property ), esc_attr( $content ) );
		}

		printf( '<meta name="twitter:card" content="%s">' . "\n", $image ? 'summary_large_image' : 'summary' );
	},
	1
);

// Разметка организации на главной: название, телефон, адрес.
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() || kedr_has_seo_plugin() ) {
			return;
		}

		$data = [
			'@context'    => 'https://schema.org',
			'@type'       => 'FurnitureStore',
			'name'        => get_bloginfo( 'name' ),
			'description' => kedr_opt( 'hero_text' ),
			'url'         => home_url( '/' ),
			'telephone'   => kedr_opt( 'phone' ),
			'email'       => kedr_opt( 'email' ),
			'address'     => [
				'@type'          => 'PostalAddress',
				'streetAddress'  => kedr_opt( 'address' ),
				'addressCountry' => 'RU',
			],
		];

		$image = kedr_meta_image();
		if ( $image ) {
			$data['image'] = $image;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG )
		);
	}
);
