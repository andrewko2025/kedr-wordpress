<?php
/**
 * Страница не найдена.
 *
 * @package Kedr
 */

get_header();
?>
<section class="notfound">
	<div class="container">
		<p class="notfound__code">404</p>
		<h1 class="notfound__title"><?php esc_html_e( 'Такой страницы нет', 'kedr' ); ?></h1>
		<p class="notfound__text"><?php esc_html_e( 'Возможно, её переместили или в адресе опечатка. Зато у нас есть много других интересных страниц.', 'kedr' ); ?></p>
		<div class="notfound__actions">
			<a class="btn btn--accent btn--lg" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'На главную', 'kedr' ); ?></a>
			<a class="btn btn--ghost btn--lg" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'Смотреть работы', 'kedr' ); ?></a>
		</div>
	</div>
</section>
<?php
get_footer();
