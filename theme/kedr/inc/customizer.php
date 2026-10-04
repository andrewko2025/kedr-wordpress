<?php
/**
 * Настройки в «Внешний вид → Настроить»: контакты и главный экран.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

/**
 * Значения по умолчанию. Контакты — заведомо вымышленные (демо-проект).
 *
 * @return array<string, string|int>
 */
function kedr_defaults(): array {
	return [
		'phone'        => '+7 (900) 000-00-00',
		'email'        => 'hello@kedr.example',
		'address'      => __( 'Москва, ул. Примерная, 1', 'kedr' ),
		'hours'        => __( 'Пн–Сб, 9:00–20:00', 'kedr' ),
		'telegram'     => '#',
		'whatsapp'     => '#',
		'vk'           => '',
		'hero_eyebrow' => __( 'Мебельная мастерская', 'kedr' ),
		'hero_title'   => __( 'Мебель на заказ, которая служит десятилетиями', 'kedr' ),
		'hero_text'    => __( 'Проектируем и изготавливаем кухни, шкафы и гардеробные под ваши размеры. Бесплатный замер и 3D-проект, производство от 12 дней, гарантия 5 лет.', 'kedr' ),
		'hero_image'   => 0,
		'stat_1_value' => '12',
		'stat_1_label' => __( 'лет на рынке', 'kedr' ),
		'stat_2_value' => '1 400+',
		'stat_2_label' => __( 'проектов сдано', 'kedr' ),
		'stat_3_value' => '5 лет',
		'stat_3_label' => __( 'гарантии', 'kedr' ),
		'demo_link'    => '',
	];
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$defaults = kedr_defaults();

		$wp_customize->add_section(
			'kedr_contacts',
			[
				'title'    => __( 'Контакты', 'kedr' ),
				'priority' => 30,
			]
		);
		$wp_customize->add_section(
			'kedr_hero',
			[
				'title'    => __( 'Главный экран', 'kedr' ),
				'priority' => 31,
			]
		);
		$wp_customize->add_section(
			'kedr_demo',
			[
				'title'       => __( 'Демо-проект', 'kedr' ),
				'description' => __( 'Ссылка в подвале рядом с пометкой о демо-версии, например на репозиторий с кодом.', 'kedr' ),
				'priority'    => 32,
			]
		);

		// id => [подпись, тип контрола, секция, функция очистки].
		$fields = [
			'phone'        => [ __( 'Телефон', 'kedr' ), 'text', 'kedr_contacts', 'sanitize_text_field' ],
			'email'        => [ __( 'E-mail', 'kedr' ), 'email', 'kedr_contacts', 'sanitize_email' ],
			'address'      => [ __( 'Адрес', 'kedr' ), 'text', 'kedr_contacts', 'sanitize_text_field' ],
			'hours'        => [ __( 'Часы работы', 'kedr' ), 'text', 'kedr_contacts', 'sanitize_text_field' ],
			'telegram'     => [ __( 'Ссылка на Telegram', 'kedr' ), 'url', 'kedr_contacts', 'esc_url_raw' ],
			'whatsapp'     => [ __( 'Ссылка на WhatsApp', 'kedr' ), 'url', 'kedr_contacts', 'esc_url_raw' ],
			'vk'           => [ __( 'Ссылка на ВКонтакте', 'kedr' ), 'url', 'kedr_contacts', 'esc_url_raw' ],
			'hero_eyebrow' => [ __( 'Надзаголовок', 'kedr' ), 'text', 'kedr_hero', 'sanitize_text_field' ],
			'hero_title'   => [ __( 'Заголовок', 'kedr' ), 'text', 'kedr_hero', 'sanitize_text_field' ],
			'hero_text'    => [ __( 'Подзаголовок', 'kedr' ), 'textarea', 'kedr_hero', 'sanitize_textarea_field' ],
			'demo_link'    => [ __( 'Ссылка «о проекте»', 'kedr' ), 'url', 'kedr_demo', 'esc_url_raw' ],
		];

		for ( $i = 1; $i <= 3; $i++ ) {
			/* translators: %d: номер показателя */
			$fields[ "stat_{$i}_value" ] = [ sprintf( __( 'Цифра %d', 'kedr' ), $i ), 'text', 'kedr_hero', 'sanitize_text_field' ];
			/* translators: %d: номер показателя */
			$fields[ "stat_{$i}_label" ] = [ sprintf( __( 'Подпись к цифре %d', 'kedr' ), $i ), 'text', 'kedr_hero', 'sanitize_text_field' ];
		}

		foreach ( $fields as $id => [ $label, $type, $section, $sanitize ] ) {
			$wp_customize->add_setting(
				$id,
				[
					'default'           => $defaults[ $id ],
					'sanitize_callback' => $sanitize,
				]
			);
			$wp_customize->add_control(
				$id,
				[
					'label'   => $label,
					'type'    => $type,
					'section' => $section,
				]
			);
		}

		$wp_customize->add_setting(
			'hero_image',
			[
				'default'           => 0,
				'sanitize_callback' => 'absint',
			]
		);
		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				'hero_image',
				[
					'label'       => __( 'Фото на главном экране', 'kedr' ),
					'description' => __( 'Если не выбрано, берётся главное фото первой работы.', 'kedr' ),
					'section'     => 'kedr_hero',
					'mime_type'   => 'image',
				]
			)
		);
	}
);
