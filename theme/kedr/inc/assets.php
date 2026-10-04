<?php
/**
 * Подключение стилей и скриптов.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Версия файла по времени изменения — чтобы браузер не держал старый кэш.
 */
function kedr_asset_version( string $relative_path ): string {
	$path = KEDR_DIR . '/' . $relative_path;

	return file_exists( $path ) ? (string) filemtime( $path ) : KEDR_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- версия в URL ломает Google Fonts.
		wp_enqueue_style( 'kedr-fonts', KEDR_FONTS_URL, [], null );
		wp_enqueue_style( 'kedr-main', KEDR_URI . '/assets/css/main.css', [], kedr_asset_version( 'assets/css/main.css' ) );

		wp_enqueue_script(
			'kedr-main',
			KEDR_URI . '/assets/js/main.js',
			[],
			kedr_asset_version( 'assets/js/main.js' ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
		wp_add_inline_script(
			'kedr-main',
			'window.kedrData = ' . wp_json_encode( [ 'calc' => kedr_calc_config() ] ) . ';',
			'before'
		);
	}
);

add_filter(
	'wp_resource_hints',
	function ( array $urls, string $relation ) {
		if ( 'preconnect' === $relation ) {
			$urls[] = [
				'href'        => 'https://fonts.gstatic.com',
				'crossorigin' => 'anonymous',
			];
		}

		return $urls;
	},
	10,
	2
);

add_action(
	'admin_enqueue_scripts',
	function ( string $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, [ 'project', 'lead' ], true ) ) {
			return;
		}

		wp_enqueue_style( 'kedr-admin', KEDR_URI . '/assets/css/admin.css', [], kedr_asset_version( 'assets/css/admin.css' ) );

		if ( 'project' === $screen->post_type && in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'kedr-admin', KEDR_URI . '/assets/js/admin.js', [], kedr_asset_version( 'assets/js/admin.js' ), true );
		}
	}
);
