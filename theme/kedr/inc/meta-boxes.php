<?php
/**
 * Поля проекта (материал, размеры, срок, цена, галерея) и цены категорий.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Описание полей проекта. Ключ хранится в мета как _kedr_{ключ}.
 *
 * @return array<string, array{label: string, placeholder?: string, type?: string}>
 */
function kedr_project_fields(): array {
	return [
		'material' => [
			'label'       => __( 'Материал фасадов', 'kedr' ),
			'placeholder' => __( 'МДФ эмаль, белый матовый', 'kedr' ),
		],
		'hardware' => [
			'label'       => __( 'Фурнитура', 'kedr' ),
			'placeholder' => __( 'Blum, доводчики', 'kedr' ),
		],
		'size'     => [
			'label'       => __( 'Размеры', 'kedr' ),
			'placeholder' => __( 'Прямая, 3,6 м', 'kedr' ),
		],
		'duration' => [
			'label'       => __( 'Срок изготовления', 'kedr' ),
			'placeholder' => __( '21 рабочий день', 'kedr' ),
		],
		'price'    => [
			'label' => __( 'Стоимость от, ₽', 'kedr' ),
			'type'  => 'number',
		],
	];
}

/**
 * Список ID через запятую → только целые положительные числа.
 */
function kedr_sanitize_id_list( $value ): string {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );

	return implode( ',', array_unique( $ids ) );
}

add_action(
	'init',
	function () {
		$auth = fn () => current_user_can( 'edit_posts' );

		foreach ( kedr_project_fields() as $key => $field ) {
			$is_number = 'number' === ( $field['type'] ?? 'text' );

			register_post_meta(
				'project',
				"_kedr_{$key}",
				[
					'type'              => $is_number ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $is_number ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => $auth,
				]
			);
		}

		register_post_meta(
			'project',
			'_kedr_gallery',
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'kedr_sanitize_id_list',
				'auth_callback'     => $auth,
			]
		);
	}
);

add_action(
	'add_meta_boxes_project',
	function () {
		add_meta_box( 'kedr-project-details', __( 'Параметры проекта', 'kedr' ), 'kedr_project_details_box', 'project', 'normal', 'high' );
		add_meta_box( 'kedr-project-gallery', __( 'Галерея проекта', 'kedr' ), 'kedr_project_gallery_box', 'project', 'normal', 'high' );
	}
);

/**
 * Метабокс «Параметры проекта».
 */
function kedr_project_details_box( WP_Post $post ): void {
	wp_nonce_field( 'kedr_project_save', 'kedr_project_nonce' );
	?>
	<div class="kedr-fields">
		<?php foreach ( kedr_project_fields() as $key => $field ) : ?>
			<p>
				<label for="kedr_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<input
					class="widefat"
					type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>"
					id="kedr_<?php echo esc_attr( $key ); ?>"
					name="kedr_<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( get_post_meta( $post->ID, "_kedr_{$key}", true ) ); ?>"
					placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
					<?php echo 'number' === ( $field['type'] ?? '' ) ? 'min="0" step="1000"' : ''; ?>
				>
			</p>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Метабокс «Галерея проекта»: выбор картинок через медиатеку.
 */
function kedr_project_gallery_box( WP_Post $post ): void {
	$ids = kedr_sanitize_id_list( get_post_meta( $post->ID, '_kedr_gallery', true ) );
	?>
	<div class="kedr-gallery-field" data-kedr-gallery>
		<input type="hidden" name="kedr_gallery" value="<?php echo esc_attr( $ids ); ?>">
		<div class="kedr-gallery-field__list" data-kedr-gallery-list>
			<?php foreach ( array_filter( explode( ',', $ids ) ) as $id ) : ?>
				<?php echo wp_get_attachment_image( (int) $id, 'thumbnail' ); ?>
			<?php endforeach; ?>
		</div>
		<p>
			<button type="button" class="button" data-kedr-gallery-pick><?php esc_html_e( 'Выбрать фото', 'kedr' ); ?></button>
			<button type="button" class="button-link button-link-delete" data-kedr-gallery-clear><?php esc_html_e( 'Очистить', 'kedr' ); ?></button>
		</p>
		<p class="description"><?php esc_html_e( 'Главное фото задаётся отдельно, справа. Здесь — дополнительные снимки для страницы проекта.', 'kedr' ); ?></p>
	</div>
	<?php
}

add_action(
	'save_post_project',
	function ( int $post_id ) {
		$nonce = isset( $_POST['kedr_project_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kedr_project_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'kedr_project_save' ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array_keys( kedr_project_fields() ) as $key ) {
			$value = isset( $_POST[ "kedr_{$key}" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "kedr_{$key}" ] ) ) : '';

			if ( '' === $value ) {
				delete_post_meta( $post_id, "_kedr_{$key}" );
			} else {
				// Санитизация по типу — в sanitize_callback из register_post_meta().
				update_post_meta( $post_id, "_kedr_{$key}", $value );
			}
		}

		$gallery = isset( $_POST['kedr_gallery'] ) ? kedr_sanitize_id_list( wp_unslash( $_POST['kedr_gallery'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- kedr_sanitize_id_list.
		update_post_meta( $post_id, '_kedr_gallery', $gallery );
	}
);

// В админке работы по умолчанию идут в том же порядке, что и на сайте.
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() && $query->is_main_query() && 'project' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
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

// Колонки в списке работ: миниатюра и цена.
add_filter(
	'manage_project_posts_columns',
	function ( array $columns ): array {
		$columns = array_slice( $columns, 0, 1, true )
			+ [ 'kedr_thumb' => __( 'Фото', 'kedr' ) ]
			+ array_slice( $columns, 1, null, true );

		$columns['kedr_price'] = __( 'Цена от', 'kedr' );

		return $columns;
	}
);

add_action(
	'manage_project_posts_custom_column',
	function ( string $column, int $post_id ) {
		if ( 'kedr_thumb' === $column ) {
			echo get_the_post_thumbnail( $post_id, [ 60, 60 ] ) ?: '—';
		}

		if ( 'kedr_price' === $column ) {
			$price = kedr_project_price( $post_id );
			echo esc_html( $price ? kedr_rub( $price ) : '—' );
		}
	},
	10,
	2
);

/*
 * Цена «от» и единица измерения у категорий работ.
 */

add_action(
	'project_cat_add_form_fields',
	function () {
		wp_nonce_field( 'kedr_term_save', 'kedr_term_nonce' );
		?>
		<div class="form-field">
			<label for="kedr_price_from"><?php esc_html_e( 'Цена от, ₽', 'kedr' ); ?></label>
			<input type="number" id="kedr_price_from" name="kedr_price_from" min="0" step="500">
		</div>
		<div class="form-field">
			<label for="kedr_price_unit"><?php esc_html_e( 'Единица', 'kedr' ); ?></label>
			<input type="text" id="kedr_price_unit" name="kedr_price_unit" placeholder="<?php esc_attr_e( 'за пог. м', 'kedr' ); ?>">
		</div>
		<?php
	}
);

add_action(
	'project_cat_edit_form_fields',
	function ( WP_Term $term ) {
		wp_nonce_field( 'kedr_term_save', 'kedr_term_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="kedr_price_from"><?php esc_html_e( 'Цена от, ₽', 'kedr' ); ?></label></th>
			<td><input type="number" id="kedr_price_from" name="kedr_price_from" min="0" step="500" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'kedr_price_from', true ) ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="kedr_price_unit"><?php esc_html_e( 'Единица', 'kedr' ); ?></label></th>
			<td><input type="text" id="kedr_price_unit" name="kedr_price_unit" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'kedr_price_unit', true ) ); ?>" placeholder="<?php esc_attr_e( 'за пог. м', 'kedr' ); ?>"></td>
		</tr>
		<?php
	}
);

/**
 * Сохраняет цену категории.
 */
function kedr_save_term_meta( int $term_id ): void {
	$nonce = isset( $_POST['kedr_term_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kedr_term_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'kedr_term_save' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	update_term_meta( $term_id, 'kedr_price_from', absint( $_POST['kedr_price_from'] ?? 0 ) );
	update_term_meta( $term_id, 'kedr_price_unit', sanitize_text_field( wp_unslash( $_POST['kedr_price_unit'] ?? '' ) ) );
}
add_action( 'created_project_cat', 'kedr_save_term_meta' );
add_action( 'edited_project_cat', 'kedr_save_term_meta' );
