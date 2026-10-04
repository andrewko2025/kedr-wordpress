<?php
/**
 * Типы записей: работы (портфолио), категории работ и заявки.
 *
 * Таксономия регистрируется раньше типа записи: её ЧПУ /raboty/kategoriya/…
 * вложены в /raboty/, и правила категорий должны проверяться первыми.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_taxonomy(
			'project_cat',
			'project',
			[
				'labels'            => [
					'name'          => __( 'Категории работ', 'kedr' ),
					'singular_name' => __( 'Категория', 'kedr' ),
					'menu_name'     => __( 'Категории', 'kedr' ),
					'all_items'     => __( 'Все категории', 'kedr' ),
					'edit_item'     => __( 'Изменить категорию', 'kedr' ),
					'view_item'     => __( 'Смотреть категорию', 'kedr' ),
					'add_new_item'  => __( 'Новая категория', 'kedr' ),
					'search_items'  => __( 'Найти категорию', 'kedr' ),
					'not_found'     => __( 'Категорий нет', 'kedr' ),
				],
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => [
					'slug'       => 'raboty/kategoriya',
					'with_front' => false,
				],
			]
		);

		register_post_type(
			'project',
			[
				'labels'        => [
					'name'                  => __( 'Работы', 'kedr' ),
					'singular_name'         => __( 'Работа', 'kedr' ),
					'menu_name'             => __( 'Работы', 'kedr' ),
					'all_items'             => __( 'Все работы', 'kedr' ),
					'add_new'               => __( 'Добавить работу', 'kedr' ),
					'add_new_item'          => __( 'Новая работа', 'kedr' ),
					'edit_item'             => __( 'Редактировать работу', 'kedr' ),
					'new_item'              => __( 'Новая работа', 'kedr' ),
					'view_item'             => __( 'Смотреть работу', 'kedr' ),
					'view_items'            => __( 'Смотреть работы', 'kedr' ),
					'search_items'          => __( 'Найти работу', 'kedr' ),
					'not_found'             => __( 'Работы не найдены', 'kedr' ),
					'not_found_in_trash'    => __( 'В корзине работ нет', 'kedr' ),
					'archives'              => __( 'Каталог работ', 'kedr' ),
					'featured_image'        => __( 'Главное фото', 'kedr' ),
					'set_featured_image'    => __( 'Выбрать главное фото', 'kedr' ),
					'remove_featured_image' => __( 'Убрать главное фото', 'kedr' ),
					'use_featured_image'    => __( 'Сделать главным фото', 'kedr' ),
				],
				'public'        => true,
				'show_in_rest'  => true,
				'menu_position' => 5,
				'menu_icon'     => 'dashicons-hammer',
				'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
				'has_archive'   => 'raboty',
				'rewrite'       => [
					'slug'       => 'raboty',
					'with_front' => false,
				],
			]
		);

		register_post_type(
			'lead',
			[
				'labels'          => [
					'name'          => __( 'Заявки', 'kedr' ),
					'singular_name' => __( 'Заявка', 'kedr' ),
					'menu_name'     => __( 'Заявки', 'kedr' ),
					'all_items'     => __( 'Все заявки', 'kedr' ),
					'edit_item'     => __( 'Заявка', 'kedr' ),
					'search_items'  => __( 'Найти заявку', 'kedr' ),
					'not_found'     => __( 'Заявок пока нет', 'kedr' ),
				],
				'public'          => false,
				'show_ui'         => true,
				'menu_position'   => 4,
				'menu_icon'       => 'dashicons-email-alt',
				'supports'        => [ 'title' ],
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				// Заявки создаёт только форма на сайте, вручную — нельзя.
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
			]
		);
	}
);
