<?php
/**
 * Запасной шаблон: записи блога, поиск и всё, для чего нет своего шаблона.
 *
 * @package Kedr
 */

get_header();

if ( is_singular() ) {
	$kedr_title = single_post_title( '', false );
} elseif ( is_search() ) {
	/* translators: %s: поисковый запрос */
	$kedr_title = sprintf( __( 'Поиск: %s', 'kedr' ), get_search_query() );
} elseif ( is_archive() ) {
	$kedr_title = wp_strip_all_tags( get_the_archive_title() );
} else {
	$kedr_title = single_post_title( '', false ) ? single_post_title( '', false ) : __( 'Статьи', 'kedr' );
}

get_template_part( 'template-parts/page-hero', null, [ 'title' => $kedr_title ] );
?>
<div class="section section--tight">
	<?php if ( is_singular() ) : ?>
		<div class="container">
			<div class="entry-content">
				<?php
				while ( have_posts() ) {
					the_post();
					the_content();
				}
				?>
			</div>
		</div>
	<?php elseif ( have_posts() ) : ?>
		<div class="container">
			<div class="post-list">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article <?php post_class( 'post-item' ); ?>>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_excerpt(); ?>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( [ 'mid_size' => 1 ] ); ?>
		</div>
	<?php else : ?>
		<div class="container">
			<p class="empty"><?php esc_html_e( 'Ничего не найдено.', 'kedr' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
