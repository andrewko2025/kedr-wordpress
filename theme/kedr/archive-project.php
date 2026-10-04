<?php
/**
 * Каталог работ с фильтром по категориям. Используется и для страниц категорий.
 *
 * @package Kedr
 */

get_header();

$kedr_current = is_tax( 'project_cat' ) ? get_queried_object() : null;
$kedr_terms   = kedr_categories( true );
$kedr_total   = (int) wp_count_posts( 'project' )->publish;

get_template_part(
	'template-parts/page-hero',
	null,
	[
		'title' => $kedr_current ? $kedr_current->name : __( 'Наши работы', 'kedr' ),
		'text'  => $kedr_current && $kedr_current->description
			? $kedr_current->description
			: __( 'Кухни, шкафы и гардеробные, которые мы спроектировали и изготовили для клиентов. Нажмите на проект, чтобы увидеть фото, материалы и сроки.', 'kedr' ),
	]
);
?>
<section class="section section--tight">
	<div class="container">
		<?php if ( $kedr_terms ) : ?>
			<nav class="filter" aria-label="<?php esc_attr_e( 'Категории работ', 'kedr' ); ?>">
				<a class="filter__item<?php echo $kedr_current ? '' : ' is-active'; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ); ?>"<?php echo $kedr_current ? '' : ' aria-current="page"'; ?>>
					<?php esc_html_e( 'Все', 'kedr' ); ?> <span><?php echo esc_html( $kedr_total ); ?></span>
				</a>
				<?php foreach ( $kedr_terms as $kedr_term ) : ?>
					<?php $kedr_active = $kedr_current && $kedr_current->term_id === $kedr_term->term_id; ?>
					<a class="filter__item<?php echo $kedr_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $kedr_term ) ); ?>"<?php echo $kedr_active ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $kedr_term->name ); ?> <span><?php echo esc_html( $kedr_term->count ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<h2 class="screen-reader-text"><?php esc_html_e( 'Список работ', 'kedr' ); ?></h2>
			<div class="project-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/project-card' );
				}
				?>
			</div>
			<?php
			the_posts_pagination(
				[
					'mid_size'           => 1,
					'prev_text'          => kedr_icon( 'chevron-left' ) . '<span class="screen-reader-text">' . esc_html__( 'Назад', 'kedr' ) . '</span>',
					'next_text'          => '<span class="screen-reader-text">' . esc_html__( 'Дальше', 'kedr' ) . '</span>' . kedr_icon( 'chevron-right' ),
					'screen_reader_text' => __( 'Страницы каталога', 'kedr' ),
				]
			);
			?>
		<?php else : ?>
			<p class="empty"><?php esc_html_e( 'В этой категории пока нет работ.', 'kedr' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
