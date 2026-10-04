<?php
/**
 * Шапка внутренней страницы: хлебные крошки, заголовок, подзаголовок.
 *
 * @package Kedr
 *
 * @var array $args { title: string, text?: string, eyebrow?: string }
 */

$kedr_args = wp_parse_args(
	$args ?? [],
	[
		'title'   => '',
		'text'    => '',
		'eyebrow' => '',
	]
);
?>
<header class="page-hero">
	<div class="container">
		<?php kedr_breadcrumbs(); ?>
		<?php if ( $kedr_args['eyebrow'] ) : ?>
			<p class="eyebrow"><?php echo esc_html( $kedr_args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h1 class="page-hero__title"><?php echo esc_html( $kedr_args['title'] ); ?></h1>
		<?php if ( $kedr_args['text'] ) : ?>
			<p class="page-hero__text"><?php echo esc_html( $kedr_args['text'] ); ?></p>
		<?php endif; ?>
	</div>
</header>
