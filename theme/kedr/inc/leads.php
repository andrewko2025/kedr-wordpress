<?php
/**
 * Заявки с сайта: приём формы, хранение в админке, уведомление на почту.
 *
 * Форма отправляется на admin-post.php. С JS ответ приходит в JSON,
 * без JS — редирект обратно с ?lead=ok|error.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_kedr_lead', 'kedr_handle_lead' );
add_action( 'admin_post_kedr_lead', 'kedr_handle_lead' );

/**
 * Обрабатывает отправку формы заявки.
 */
function kedr_handle_lead(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce проверяется ниже.
	$is_ajax  = 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' ) ) );
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

	$respond = function ( bool $ok, string $message, int $status = 200 ) use ( $is_ajax, $redirect ) {
		if ( $is_ajax ) {
			wp_send_json( [ 'success' => $ok, 'data' => [ 'message' => $message ] ], $status );
		}

		wp_safe_redirect( add_query_arg( 'lead', $ok ? 'ok' : 'error', remove_query_arg( 'lead', $redirect ) ) . '#lead' );
		exit;
	};

	$nonce = sanitize_text_field( wp_unslash( $_POST['kedr_lead_nonce'] ?? '' ) );
	if ( ! wp_verify_nonce( $nonce, 'kedr_lead' ) ) {
		$respond( false, __( 'Страница устарела. Обновите её и отправьте заявку ещё раз.', 'kedr' ), 403 );
	}

	// Скрытое поле-ловушка: человек его не видит, бот заполняет. Делаем вид, что всё хорошо.
	if ( ! empty( $_POST['website'] ) ) {
		$respond( true, __( 'Заявка отправлена.', 'kedr' ) );
	}

	$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$message    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	$calc       = sanitize_text_field( wp_unslash( $_POST['calc'] ?? '' ) );
	$project_id = absint( $_POST['project_id'] ?? 0 );
	$consent    = ! empty( $_POST['consent'] );
	// phpcs:enable

	$digits = preg_replace( '/\D/', '', $phone );

	if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 80 ) {
		$respond( false, __( 'Укажите, как к вам обращаться.', 'kedr' ), 422 );
	}
	if ( strlen( $digits ) < 10 || strlen( $digits ) > 15 ) {
		$respond( false, __( 'Проверьте номер телефона.', 'kedr' ), 422 );
	}
	if ( ! $consent ) {
		$respond( false, __( 'Нужно согласие на обработку персональных данных.', 'kedr' ), 422 );
	}
	if ( $project_id && 'project' !== get_post_type( $project_id ) ) {
		$project_id = 0;
	}

	// Не чаще одной заявки в 30 секунд с одного адреса.
	$ip_hash = md5( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) . wp_salt() );
	if ( get_transient( "kedr_lead_{$ip_hash}" ) ) {
		$respond( false, __( 'Заявка уже отправлена. Если нужно что-то добавить — позвоните нам.', 'kedr' ), 429 );
	}
	set_transient( "kedr_lead_{$ip_hash}", 1, 30 );

	$lead_id = kedr_create_lead(
		[
			'name'       => $name,
			'phone'      => $phone,
			'message'    => mb_substr( $message, 0, 2000 ),
			'calc'       => $calc,
			'project_id' => $project_id,
			'source'     => esc_url_raw( $redirect ),
		]
	);

	if ( ! $lead_id ) {
		$respond( false, __( 'Не удалось сохранить заявку. Позвоните нам, пожалуйста.', 'kedr' ), 500 );
	}

	kedr_notify_about_lead( $lead_id );

	$respond( true, __( 'Спасибо! Перезвоним в течение 15 минут в рабочее время.', 'kedr' ) );
}

/**
 * Сохраняет заявку. Используется формой и демо-наполнением.
 *
 * @param array{name: string, phone: string, message?: string, calc?: string, project_id?: int, source?: string} $data Данные заявки.
 * @return int ID заявки или 0.
 */
function kedr_create_lead( array $data ): int {
	$lead_id = wp_insert_post(
		[
			'post_type'   => 'lead',
			'post_status' => 'publish',
			/* translators: 1: имя, 2: телефон */
			'post_title'  => sprintf( __( '%1$s, %2$s', 'kedr' ), $data['name'], $data['phone'] ),
		],
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		return 0;
	}

	foreach ( [ 'name', 'phone', 'message', 'calc', 'project_id', 'source' ] as $key ) {
		if ( ! empty( $data[ $key ] ) ) {
			update_post_meta( $lead_id, "_kedr_{$key}", $data[ $key ] );
		}
	}
	update_post_meta( $lead_id, '_kedr_new', 1 );

	return $lead_id;
}

/**
 * Письмо администратору о новой заявке.
 */
function kedr_notify_about_lead( int $lead_id ): void {
	$lines = [];
	foreach ( kedr_lead_fields() as $key => $label ) {
		$value = kedr_lead_value( $lead_id, $key );
		if ( '' !== $value ) {
			$lines[] = "{$label}: {$value}";
		}
	}
	$lines[] = '';
	$lines[] = admin_url( "post.php?post={$lead_id}&action=edit" );

	$to = apply_filters( 'kedr_lead_recipients', get_option( 'admin_email' ) );

	/* translators: %s: название сайта */
	wp_mail( $to, sprintf( __( 'Новая заявка с сайта «%s»', 'kedr' ), get_bloginfo( 'name' ) ), implode( "\n", $lines ) );
}

/**
 * Поля заявки для админки и письма.
 *
 * @return array<string, string>
 */
function kedr_lead_fields(): array {
	return [
		'name'       => __( 'Имя', 'kedr' ),
		'phone'      => __( 'Телефон', 'kedr' ),
		'message'    => __( 'Сообщение', 'kedr' ),
		'calc'       => __( 'Расчёт из калькулятора', 'kedr' ),
		'project_id' => __( 'Интересует проект', 'kedr' ),
		'source'     => __( 'Страница', 'kedr' ),
	];
}

/**
 * Значение поля заявки в читаемом виде.
 */
function kedr_lead_value( int $lead_id, string $key ): string {
	$value = (string) get_post_meta( $lead_id, "_kedr_{$key}", true );

	if ( 'project_id' === $key && $value ) {
		return get_the_title( (int) $value );
	}

	return $value;
}

/*
 * Админка заявок.
 */

add_filter(
	'manage_lead_posts_columns',
	fn () => [
		'cb'            => '<input type="checkbox">',
		'title'         => __( 'Клиент', 'kedr' ),
		'kedr_phone'    => __( 'Телефон', 'kedr' ),
		'kedr_request'  => __( 'Запрос', 'kedr' ),
		'date'          => __( 'Дата', 'kedr' ),
	]
);

add_action(
	'manage_lead_posts_custom_column',
	function ( string $column, int $lead_id ) {
		if ( 'kedr_phone' === $column ) {
			$phone = kedr_lead_value( $lead_id, 'phone' );
			printf( '<a href="%s">%s</a>', esc_url( kedr_phone_href( $phone ) ), esc_html( $phone ) );
		}

		if ( 'kedr_request' === $column ) {
			$parts = array_filter(
				[
					kedr_lead_value( $lead_id, 'message' ),
					kedr_lead_value( $lead_id, 'calc' ),
					kedr_lead_value( $lead_id, 'project_id' ),
				]
			);
			echo esc_html( $parts ? wp_trim_words( implode( ' · ', $parts ), 18 ) : '—' );
		}
	},
	10,
	2
);

// Новые заявки выделяем жирным в списке.
add_filter(
	'post_class',
	function ( array $classes, array $css_classes, int $post_id ) {
		if ( is_admin() && 'lead' === get_post_type( $post_id ) && get_post_meta( $post_id, '_kedr_new', true ) ) {
			$classes[] = 'kedr-lead-new';
		}

		return $classes;
	},
	10,
	3
);

add_action(
	'add_meta_boxes_lead',
	function () {
		add_meta_box( 'kedr-lead-data', __( 'Данные заявки', 'kedr' ), 'kedr_lead_box', 'lead', 'normal', 'high' );
	}
);

/**
 * Метабокс с данными заявки (только чтение). При просмотре заявка перестаёт быть новой.
 */
function kedr_lead_box( WP_Post $post ): void {
	delete_post_meta( $post->ID, '_kedr_new' );
	?>
	<table class="widefat striped kedr-lead-table">
		<tbody>
			<?php foreach ( kedr_lead_fields() as $key => $label ) : ?>
				<?php $value = kedr_lead_value( $post->ID, $key ); ?>
				<?php if ( '' === $value ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td>
						<?php if ( 'phone' === $key ) : ?>
							<a href="<?php echo esc_url( kedr_phone_href( $value ) ); ?>"><?php echo esc_html( $value ); ?></a>
						<?php elseif ( 'source' === $key ) : ?>
							<a href="<?php echo esc_url( $value ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $value ); ?></a>
						<?php else : ?>
							<?php echo nl2br( esc_html( $value ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

// Счётчик новых заявок рядом с пунктом меню.
add_action(
	'admin_menu',
	function () {
		global $menu;

		$new = count(
			get_posts(
				[
					'post_type'      => 'lead',
					'posts_per_page' => 99,
					'fields'         => 'ids',
					'meta_key'       => '_kedr_new', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'no_found_rows'  => true,
				]
			)
		);

		if ( ! $new ) {
			return;
		}

		foreach ( $menu as $i => $item ) {
			if ( 'edit.php?post_type=lead' === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $new . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				break;
			}
		}
	}
);
