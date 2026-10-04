<?php
/**
 * Хелперы для шаблонов.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значение настройки из Customizer с дефолтом из kedr_defaults().
 */
function kedr_opt( string $key ): string {
	$defaults = kedr_defaults();

	return (string) get_theme_mod( $key, $defaults[ $key ] ?? '' );
}

/**
 * Inline SVG-иконка (контурные иконки в стиле Lucide, 24×24).
 */
function kedr_icon( string $name, string $class = '' ): string {
	static $icons = [
		'arrow-right'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'calculator'    => '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M8 6h8"/><path d="M16 14v4"/><path d="M16 10h.01"/><path d="M12 10h.01"/><path d="M8 10h.01"/><path d="M12 14h.01"/><path d="M8 14h.01"/><path d="M12 18h.01"/><path d="M8 18h.01"/>',
		'cedar'         => '<path d="M12 1.5 6.2 8.6h3.2L5.6 13.7h3.7L4.5 19.5H11V23h2v-3.5h6.5l-4.8-5.8h3.7l-3.8-5.1h3.2Z"/>',
		'check'         => '<path d="M20 6 9 17l-5-5"/>',
		'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
		'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
		'clock'         => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
		'close'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'hammer'        => '<path d="m15 12-8.5 8.5a2.12 2.12 0 1 1-3-3L12 9"/><path d="M17.64 15 22 10.64"/><path d="m20.91 11.7-1.25-1.25a3.2 3.2 0 0 1-.93-2.25v-.86L16.01 4.6a5.56 5.56 0 0 0-3.94-1.64H9l.92.82A6.18 6.18 0 0 1 12 8.4v1.56l2 2h2.47l2.26 1.91"/>',
		'image'         => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
		'leaf'          => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
		'mail'          => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'message'       => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
		'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>',
		'pin'           => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'plus'          => '<path d="M5 12h14"/><path d="M12 5v14"/>',
		'ruler'         => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
		'send'          => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
		'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
		'star'          => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01Z"/>',
	];

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$paint = in_array( $name, [ 'cedar', 'star' ], true )
		? 'fill="currentColor"'
		: 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';

	return sprintf(
		'<svg class="icon %1$s" viewBox="0 0 24 24" %2$s aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $class ),
		$paint,
		$icons[ $name ]
	);
}

/**
 * Выводит иконку.
 */
function kedr_the_icon( string $name, string $class = '' ): void {
	echo kedr_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- статичный SVG.
}

/**
 * Заглушка вместо фото, если у записи нет картинки.
 */
function kedr_placeholder( string $label = '' ): string {
	$a11y = '' !== $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"';

	return '<span class="media-ph"' . $a11y . '>' . kedr_icon( 'image' ) . '</span>';
}

/**
 * Цена в рублях с неразрывными пробелами: «38 000 ₽».
 */
function kedr_rub( int $value ): string {
	return number_format( $value, 0, ',', "\u{00A0}" ) . "\u{00A0}₽";
}

/**
 * Русское склонение после числа: 1 работа, 3 работы, 5 работ.
 */
function kedr_plural( int $n, string $one, string $few, string $many ): string {
	$mod10  = $n % 10;
	$mod100 = $n % 100;

	if ( 1 === $mod10 && 11 !== $mod100 ) {
		return $one;
	}

	return ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) ? $few : $many;
}

/**
 * Ссылка tel: из телефона в любом формате.
 */
function kedr_phone_href( string $phone ): string {
	return 'tel:' . preg_replace( '/[^\d+]/', '', $phone );
}

/**
 * Логотип: загруженный в Customizer или текстовый знак по умолчанию.
 */
function kedr_brand(): void {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<span class="brand__mark"><?php kedr_the_icon( 'cedar' ); ?></span>
		<span class="brand__text">
			<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
			<span class="brand__tag"><?php bloginfo( 'description' ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Ссылки на мессенджеры и соцсети, заполненные в Customizer.
 *
 * @return array<int, array{label: string, url: string}>
 */
function kedr_socials(): array {
	$labels = [
		'telegram' => 'Telegram',
		'whatsapp' => 'WhatsApp',
		'vk'       => 'ВКонтакте',
	];

	$links = [];
	foreach ( $labels as $key => $label ) {
		$url = kedr_opt( $key );
		if ( '' !== $url ) {
			$links[] = [
				'label' => $label,
				'url'   => $url,
			];
		}
	}

	return $links;
}

/**
 * Заголовок секции: надзаголовок, h2, описание и необязательная кнопка.
 *
 * @param array{eyebrow?: string, title: string, text?: string, id?: string, link?: string, link_label?: string} $args Параметры.
 */
function kedr_section_head( array $args ): void {
	$args = wp_parse_args(
		$args,
		[
			'eyebrow'    => '',
			'title'      => '',
			'text'       => '',
			'id'         => '',
			'link'       => '',
			'link_label' => '',
		]
	);
	?>
	<div class="section-head" data-reveal>
		<div class="section-head__text">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<h2<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>><?php echo esc_html( $args['title'] ); ?></h2>
			<?php if ( $args['text'] ) : ?>
				<p><?php echo esc_html( $args['text'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( $args['link'] ) : ?>
			<a class="btn btn--ghost" href="<?php echo esc_url( $args['link'] ); ?>">
				<?php echo esc_html( $args['link_label'] ); ?>
				<?php kedr_the_icon( 'arrow-right' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Хлебные крошки для внутренних страниц.
 */
function kedr_breadcrumbs(): void {
	$items = [ [ __( 'Главная', 'kedr' ), home_url( '/' ) ] ];
	$works = [ __( 'Работы', 'kedr' ), get_post_type_archive_link( 'project' ) ];

	if ( is_singular( 'project' ) ) {
		$items[] = $works;
		$terms   = get_the_terms( get_queried_object_id(), 'project_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$items[] = [ $terms[0]->name, get_term_link( $terms[0] ) ];
		}
		$items[] = [ get_the_title( get_queried_object_id() ), '' ];
	} elseif ( is_tax( 'project_cat' ) ) {
		$items[] = $works;
		$items[] = [ single_term_title( '', false ), '' ];
	} elseif ( is_post_type_archive( 'project' ) ) {
		$items[] = [ $works[0], '' ];
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $ancestor ) {
			$items[] = [ get_the_title( $ancestor ), get_permalink( $ancestor ) ];
		}
		$items[] = [ get_the_title( get_queried_object_id() ), '' ];
	} elseif ( is_singular() ) {
		$items[] = [ get_the_title( get_queried_object_id() ), '' ];
	} elseif ( is_search() ) {
		$items[] = [ __( 'Поиск', 'kedr' ), '' ];
	} elseif ( is_archive() ) {
		$items[] = [ wp_strip_all_tags( get_the_archive_title() ), '' ];
	}
	?>
	<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Хлебные крошки', 'kedr' ); ?>">
		<ol>
			<?php foreach ( $items as [ $label, $url ] ) : ?>
				<li>
					<?php if ( $url && ! is_wp_error( $url ) ) : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Меню по умолчанию, пока в админке не назначено своё.
 *
 * @param array $args Аргументы wp_nav_menu().
 */
function kedr_menu_fallback( array $args ): void {
	$links = [ get_post_type_archive_link( 'project' ) => __( 'Работы', 'kedr' ) ];
	foreach ( get_pages( [ 'sort_column' => 'menu_order' ] ) as $page ) {
		if ( (int) get_option( 'page_on_front' ) !== $page->ID ) {
			$links[ get_permalink( $page ) ] = get_the_title( $page );
		}
	}

	echo '<ul class="' . esc_attr( $args['menu_class'] ?? '' ) . '">';
	foreach ( $links as $url => $label ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Значение поля проекта (материал, размеры, срок…).
 */
function kedr_project_meta( int $post_id, string $key ): string {
	return (string) get_post_meta( $post_id, "_kedr_{$key}", true );
}

/**
 * Стоимость проекта «от», ₽.
 */
function kedr_project_price( int $post_id ): int {
	return (int) get_post_meta( $post_id, '_kedr_price', true );
}

/**
 * Характеристики проекта для таблицы «Параметры»: подпись => значение.
 *
 * @return array<string, string>
 */
function kedr_project_specs( int $post_id ): array {
	$specs = [];
	foreach ( kedr_project_fields() as $key => $field ) {
		$value = 'price' === $key ? '' : kedr_project_meta( $post_id, $key );
		if ( '' !== $value ) {
			$specs[ $field['label'] ] = $value;
		}
	}

	return $specs;
}

/**
 * ID картинок проекта: главное фото и галерея.
 *
 * @return int[]
 */
function kedr_project_gallery( int $post_id ): array {
	$ids = array_map( 'absint', explode( ',', kedr_project_meta( $post_id, 'gallery' ) ) );
	array_unshift( $ids, (int) get_post_thumbnail_id( $post_id ) );

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Названия категорий проекта через запятую.
 */
function kedr_project_cats( int $post_id ): string {
	$terms = get_the_terms( $post_id, 'project_cat' );

	return $terms && ! is_wp_error( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';
}

/**
 * Главное фото проекта или заглушка.
 */
function kedr_project_image( int $post_id, string $size = 'kedr-card' ): string {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, $size );
	}

	return kedr_placeholder( get_the_title( $post_id ) );
}

/**
 * Категории работ в порядке создания (кухни идут первыми, как в прайсе).
 *
 * @return WP_Term[]
 */
function kedr_categories( bool $hide_empty = false ): array {
	$terms = get_terms(
		[
			'taxonomy'   => 'project_cat',
			'hide_empty' => $hide_empty,
			'orderby'    => 'term_id',
		]
	);

	return is_wp_error( $terms ) ? [] : $terms;
}

/**
 * Обложка категории — фото первой работы из неё.
 */
function kedr_term_image( WP_Term $term, string $size = 'kedr-card' ): string {
	$ids = get_posts(
		[
			'post_type'      => 'project',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
			'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- одна запись.
			'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'project_cat',
					'terms'    => $term->term_id,
				],
			],
		]
	);

	return $ids ? get_the_post_thumbnail( $ids[0], $size ) : kedr_placeholder( $term->name );
}

/**
 * Цена категории «от» и единица измерения из настроек категории.
 *
 * @return array{price: int, unit: string}
 */
function kedr_term_price_data( WP_Term $term ): array {
	return [
		'price' => (int) get_term_meta( $term->term_id, 'kedr_price_from', true ),
		'unit'  => (string) get_term_meta( $term->term_id, 'kedr_price_unit', true ),
	];
}

/**
 * Цена категории одной строкой: «от 38 000 ₽ за пог. м» или пустая строка.
 */
function kedr_term_price( WP_Term $term ): string {
	[ 'price' => $price, 'unit' => $unit ] = kedr_term_price_data( $term );
	if ( ! $price ) {
		return '';
	}

	/* translators: %s: цена */
	return trim( sprintf( __( 'от %s', 'kedr' ), kedr_rub( $price ) ) . ' ' . $unit );
}
