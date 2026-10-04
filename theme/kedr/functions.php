<?php
/**
 * Тема «Кедр»: подключение модулей.
 *
 * @package Kedr
 */

defined( 'ABSPATH' ) || exit;

define( 'KEDR_VERSION', '1.0.0' );
define( 'KEDR_DIR', get_template_directory() );
define( 'KEDR_URI', get_template_directory_uri() );
define( 'KEDR_FONTS_URL', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap' );

$kedr_modules = [
	'setup',         // Поддержка возможностей WP, меню, размеры картинок.
	'content',       // Тексты блоков главной и настройки калькулятора.
	'template-tags', // Хелперы для шаблонов.
	'post-types',    // Работы, категории работ, заявки.
	'meta-boxes',    // Поля проекта, галерея, цены категорий.
	'customizer',    // Контакты и главный экран.
	'leads',         // Приём и просмотр заявок.
	'assets',        // Стили и скрипты.
	'seo',           // Мета-теги, Open Graph, schema.org.
];

foreach ( $kedr_modules as $kedr_module ) {
	require KEDR_DIR . "/inc/{$kedr_module}.php";
}

unset( $kedr_modules, $kedr_module );
