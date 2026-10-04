<?php
/**
 * Демо-контент для темы «Кедр».
 *
 * Запускается из setup.sh через `wp eval-file`, а в WordPress Playground —
 * из blueprint.json (там папку с фото задаёт константа KEDR_SEED_IMAGES).
 *
 * Повторный запуск ничего не дублирует: записи ищутся по slug и обновляются.
 * Фото берутся из /seed/images (папка seed/images в проекте), если они есть:
 *   {slug}.jpg — главное фото работы, {slug}-2.jpg, {slug}-3.jpg — галерея,
 *   hero.jpg — фото на главном экране. Без фото тема покажет заглушки.
 *
 * @package Kedr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

defined( 'KEDR_SEED_IMAGES' ) || define( 'KEDR_SEED_IMAGES', '/seed/images' );

/**
 * Сообщение о ходе наполнения: в WP-CLI — в консоль, в Playground — в лог.
 * Уровень error прерывает выполнение.
 */
function kedr_seed_say( string $message, string $level = 'log' ): void {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::$level( $message );
		return;
	}

	if ( 'error' === $level ) {
		throw new RuntimeException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	error_log( "kedr-seed: {$message}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Собирает разметку блоков Gutenberg из простого списка.
 *
 * @param array<int, array{0: string, 1: string|string[]}> $items [тип, содержимое]: p, h2, ul, quote.
 */
function kedr_seed_blocks( array $items ): string {
	$html = [];
	foreach ( $items as [ $type, $content ] ) {
		switch ( $type ) {
			case 'h2':
				$html[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$content}</h2>\n<!-- /wp:heading -->";
				break;
			case 'ul':
				$lis    = implode( '', array_map( fn ( $li ) => "<!-- wp:list-item -->\n<li>{$li}</li>\n<!-- /wp:list-item -->", $content ) );
				$html[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">{$lis}</ul>\n<!-- /wp:list -->";
				break;
			case 'quote':
				[ $text, $cite ] = $content;
				$html[]          = "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph --><cite>{$cite}</cite></blockquote>\n<!-- /wp:quote -->";
				break;
			default:
				$html[] = "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->";
		}
	}

	return implode( "\n\n", $html );
}

/**
 * Создаёт или обновляет запись по slug.
 */
function kedr_seed_post( array $args ): int {
	$existing = get_page_by_path( $args['post_name'], OBJECT, $args['post_type'] );
	if ( $existing ) {
		$args['ID'] = $existing->ID;
	}

	$args += [ 'post_status' => 'publish' ];
	$id    = wp_insert_post( wp_slash( $args ), true );

	if ( is_wp_error( $id ) ) {
		kedr_seed_say( "{$args['post_name']}: " . $id->get_error_message(), 'error' );
	}

	return (int) $id;
}

/**
 * Загружает картинку из папки seed в медиатеку (один раз).
 */
function kedr_seed_image( string $filename, string $alt, int $parent = 0 ): int {
	$existing = get_posts(
		[
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_kedr_seed_file',
			'meta_value'     => $filename,
		]
	);
	if ( $existing ) {
		return (int) $existing[0];
	}

	$path = KEDR_SEED_IMAGES . '/' . $filename;
	if ( ! is_readable( $path ) ) {
		return 0;
	}

	// media_handle_sideload() перемещает файл — работаем с копией, оригинал read-only.
	$tmp = wp_tempnam( $filename );
	copy( $path, $tmp );

	$id = media_handle_sideload(
		[
			'name'     => $filename,
			'tmp_name' => $tmp,
		],
		$parent,
		$alt
	);

	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		kedr_seed_say( "{$filename}: " . $id->get_error_message(), 'warning' );
		return 0;
	}

	update_post_meta( $id, '_kedr_seed_file', $filename );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );

	return (int) $id;
}

/*
 * Категории работ.
 */

$categories = [
	'kuhni'       => [ 'Кухни', 'Прямые, угловые и островные кухни под ваши размеры и технику.', 38000 ],
	'shkafy-kupe' => [ 'Шкафы-купе', 'Встроенные и корпусные шкафы с раздвижными дверями.', 32000 ],
	'garderobnye' => [ 'Гардеробные', 'Системы хранения для гардеробных комнат любой формы.', 28000 ],
	'detskie'     => [ 'Детские', 'Кровати, столы и шкафы из безопасных материалов.', 30000 ],
];

$term_ids = [];
foreach ( $categories as $slug => [ $name, $description, $price ] ) {
	$term = term_exists( $slug, 'project_cat' );
	$term = $term
		? wp_update_term( (int) $term['term_id'], 'project_cat', [ 'name' => $name, 'description' => $description ] )
		: wp_insert_term( $name, 'project_cat', [ 'slug' => $slug, 'description' => $description ] );

	if ( is_wp_error( $term ) ) {
		kedr_seed_say( "{$slug}: " . $term->get_error_message(), 'error' );
	}

	$term_ids[ $slug ] = (int) $term['term_id'];
	update_term_meta( $term_ids[ $slug ], 'kedr_price_from', $price );
	update_term_meta( $term_ids[ $slug ], 'kedr_price_unit', 'за пог. м' );
}
kedr_seed_say( '  категорий: ' . count( $term_ids ) );

/*
 * Работы. Порядок перемешан, чтобы на главной были разные категории.
 */

$projects = [
	[
		'slug'     => 'kuhnya-skandi',
		'cat'      => 'kuhni',
		'title'    => 'Светлая кухня в скандинавском стиле',
		'excerpt'  => 'Кухня до потолка для небольшой квартиры: белые фасады, тёмная столешница и вдвое больше места для хранения, чем было.',
		'material' => 'МДФ эмаль, белый глянец',
		'hardware' => 'Blum, доводчики на всех ящиках',
		'size'     => 'Прямая, 3,6 м',
		'duration' => '24 рабочих дня',
		'price'    => 420000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Хозяева небольшой квартиры хотели светлую кухню, которая не загромождает пространство, но вмещает всю посуду и запасы.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Подняли верхний ярус до потолка — плюс 0,8 м³ для хранения',
					'Фасады — МДФ в глянцевой эмали: светлая кухня визуально больше',
					'Столешница — влагостойкий HPL-пластик графитового цвета',
					'У окна оставили свободную зону для обеденного стола',
				],
			],
			[ 'p', 'От замера до монтажа прошло 24 рабочих дня, сборка на объекте заняла один день.' ],
		],
	],
	[
		'slug'     => 'shkaf-kupe-zerkalo',
		'cat'      => 'shkafy-kupe',
		'title'    => 'Шкаф-купе с зеркальными фасадами',
		'excerpt'  => 'Встроенный шкаф во всю стену спальни: зеркала визуально расширяют комнату, внутри — отдельные зоны для двоих.',
		'material' => 'Шпон дуба + зеркало серебро',
		'hardware' => 'Алюминиевый профиль, плавный ход',
		'size'     => '2,8 × 2,7 м',
		'duration' => '14 рабочих дней',
		'price'    => 165000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Спальня 14 м², в которой нужно разместить одежду двух взрослых и не «съесть» пространство громоздким шкафом.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Встроили шкаф в нишу от пола до потолка — без зазоров и пыльных антресолей сверху',
					'Центральная дверь из шпона дуба и две зеркальных, все на бесшумных роликах',
					'Внутри — две независимые секции со штангами, ящиками и полками',
					'Подсветка включается при открытии двери',
				],
			],
		],
	],
	[
		'slug'     => 'garderobnaya-otkrytaya',
		'cat'      => 'garderobnye',
		'title'    => 'Гардеробная с открытыми полками',
		'excerpt'  => 'Гардеробная комната без дверей: всё на виду, а подсветка включается датчиком движения.',
		'material' => 'ЛДСП белая, открытые полки',
		'hardware' => 'Выдвижные брючницы и корзины',
		'size'     => '2,4 × 1,8 м',
		'duration' => '16 рабочих дней',
		'price'    => 210000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Сделать из небольшой кладовой полноценную гардеробную, где каждая вещь видна и не нужно ничего искать.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Белая система без дверей вдоль обеих стен — проход остался свободным',
					'Выдвижные брючницы, корзины для белья и обувные полки',
					'Отдельная секция для чемоданов и сезонных вещей',
					'LED-подсветка с датчиком движения',
				],
			],
		],
	],
	[
		'slug'     => 'detskaya-shkolnitsa',
		'cat'      => 'detskie',
		'title'    => 'Детская для школьницы',
		'excerpt'  => 'Шкаф во всю стену, рабочее место и полки для игрушек — в комнате 12 м² хватило места и для учёбы, и для игр.',
		'material' => 'ЛДСП белая + МДФ эмаль',
		'hardware' => 'Blum, мягкие доводчики',
		'size'     => 'Комната 12 м²',
		'duration' => '18 рабочих дней',
		'price'    => 230000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Девочка пошла в школу, и детская перестала вмещать одежду, учебники и игрушки. Нужна была мебель, которая будет расти вместе с ребёнком.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Встроенный шкаф во всю стену с отделением для школьной формы',
					'Письменный стол с ящиками и надстройкой для учебников',
					'Открытые полки с подсветкой для игрушек и книг',
					'Все углы скруглены, фасады покрыты безопасной эмалью',
				],
			],
		],
	],
	[
		'slug'     => 'kuhnya-ostrov',
		'cat'      => 'kuhni',
		'title'    => 'Угловая кухня с островом',
		'excerpt'  => 'Кухня для загородного дома: остров с барной зоной, высокие пеналы и отдельная кладовая для запасов.',
		'material' => 'МДФ «графит» + столешница из гранита',
		'hardware' => 'Hettich',
		'size'     => '3,2 × 2,1 м + остров 1,6 м',
		'duration' => '28 рабочих дней',
		'price'    => 510000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Большая семья, которая много готовит и часто собирает гостей. Нужна кухня, где одновременно могут работать два человека.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Г-образная кухня с рабочим треугольником «мойка — плита — холодильник»',
					'Остров с барной стойкой — здесь завтракают и делают уроки',
					'Высокие пеналы с выдвижными корзинами для запасов',
					'Столешницы и остров — натуральный гранит, которому не страшны горячее и ножи',
				],
			],
		],
	],
	[
		'slug'     => 'garderobnaya-mansarda',
		'cat'      => 'garderobnye',
		'title'    => 'Гардеробная в мансарде',
		'excerpt'  => 'Повторили линию скоса крыши, чтобы не потерять ни сантиметра: короткие штанги, глубокие ящики и пантограф.',
		'material' => 'Шпон американского ореха',
		'hardware' => 'Hettich, пантограф, LED-профиль',
		'size'     => 'Скос 30°, длина 3,5 м',
		'duration' => '20 рабочих дней',
		'price'    => 260000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Мансардная спальня со скошенным потолком: стандартные шкафы туда не встают, а места для хранения не хватает.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Корпуса повторяют угол скоса — без пустот за шкафом',
					'Под низкой частью — глубокие выдвижные ящики',
					'Под высокой — штанги для длинной одежды и пантограф',
					'Отделка шпоном ореха и линейная подсветка вдоль скоса',
				],
			],
		],
	],
	[
		'slug'     => 'kuhnya-loft',
		'cat'      => 'kuhni',
		'title'    => 'Кухня-лофт со столешницей под бетон',
		'excerpt'  => 'Фасады под дерево на фоне кирпича, металл и столешница под бетон — бюджетный лофт без компромиссов в надёжности.',
		'material' => 'ЛДСП «дуб вотан» + металл',
		'hardware' => 'Blum',
		'size'     => 'Прямая, 4,1 м',
		'duration' => '21 рабочий день',
		'price'    => 340000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Лофт в ограниченном бюджете: заказчик хотел характерный интерьер, но без дорогих материалов.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Фасады из ЛДСП с фактурой дерева вместо шпона — в три раза дешевле',
					'Обеденный стол и табуреты на металлическом каркасе — в одном стиле с кухней',
					'Столешница из влагостойкого HPL-пластика под бетон',
					'Сэкономленный бюджет ушёл в фурнитуру Blum с доводчиками',
				],
			],
		],
	],
	[
		'slug'     => 'shkaf-prihozhaya',
		'cat'      => 'shkafy-kupe',
		'title'    => 'Встроенный шкаф в нишу прихожей',
		'excerpt'  => 'Компактная система хранения в нише глубиной 60 см: верхняя одежда, обувь, пылесос и сезонные вещи.',
		'material' => 'МДФ глянец «графит» + шпон дуба',
		'hardware' => 'Blum, открывание нажатием',
		'size'     => '1,9 × 2,6 м',
		'duration' => '12 рабочих дней',
		'price'    => 118000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Узкая прихожая, где обувь стояла на полу, а пылесосу не было места. Нужно было уместить всё в нишу 1,9 метра.' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Высокий шкаф с глянцевыми фасадами без ручек — открывается нажатием',
					'Подвесная тумба из дуба и скамья, на которой удобно обуваться',
					'Отдельная секция с розеткой для пылесоса',
					'Антресоль для сезонных вещей',
				],
			],
		],
	],
	[
		'slug'     => 'detskaya-cherdak',
		'cat'      => 'detskie',
		'title'    => 'Детская с кроватью-чердаком',
		'excerpt'  => 'Кровать на втором ярусе, под ней — уголок для игр и шкаф. Все углы скруглены, лестница с широкими ступенями.',
		'material' => 'Массив сосны, белая эмаль',
		'hardware' => 'Hettich',
		'size'     => 'Кровать 90 × 200 см',
		'duration' => '22 рабочих дня',
		'price'    => 185000,
		'content'  => [
			[ 'h2', 'Задача' ],
			[ 'p', 'Комната 9 м² для ребёнка шести лет: нужно освободить пол для игр и при этом сделать мебель «на вырост».' ],
			[ 'h2', 'Что сделали' ],
			[
				'ul',
				[
					'Кровать-чердак из массива сосны с высоким бортиком',
					'Лестница-комод: ступени одновременно служат ящиками',
					'Под кроватью — игровой домик, который позже станет рабочим местом',
					'Покрытие — эмаль на водной основе, без запаха',
				],
			],
		],
	],
];

$project_ids = [];
foreach ( $projects as $index => $project ) {
	$id = kedr_seed_post(
		[
			'post_type'    => 'project',
			'post_name'    => $project['slug'],
			'post_title'   => $project['title'],
			'post_excerpt' => $project['excerpt'],
			'post_content' => kedr_seed_blocks( $project['content'] ),
			'menu_order'   => $index + 1,
		]
	);

	wp_set_object_terms( $id, $term_ids[ $project['cat'] ], 'project_cat' );

	foreach ( [ 'material', 'hardware', 'size', 'duration', 'price' ] as $key ) {
		update_post_meta( $id, "_kedr_{$key}", $project[ $key ] );
	}

	$thumb = kedr_seed_image( "{$project['slug']}.jpg", $project['title'], $id );
	if ( $thumb ) {
		set_post_thumbnail( $id, $thumb );
	}

	$gallery = array_filter(
		[
			kedr_seed_image( "{$project['slug']}-2.jpg", $project['title'], $id ),
			kedr_seed_image( "{$project['slug']}-3.jpg", $project['title'], $id ),
		]
	);
	update_post_meta( $id, '_kedr_gallery', implode( ',', $gallery ) );

	$project_ids[ $project['slug'] ] = $id;
}
kedr_seed_say( '  работ: ' . count( $project_ids ) );

$hero = kedr_seed_image( 'hero.jpg', 'Мебель на заказ от мастерской «Кедр»' );
if ( $hero ) {
	set_theme_mod( 'hero_image', $hero );
}

/*
 * Страницы.
 */

$pages = [
	'glavnaya' => [
		'title'   => 'Главная',
		'excerpt' => '',
		'content' => '',
	],
	'uslugi'   => [
		'title'    => 'Услуги и цены',
		'template' => 'page-templates/services.php',
		'excerpt'  => 'Цены указаны за погонный метр в базовой комплектации. Итоговая стоимость зависит от размеров, материалов и фурнитуры — прикиньте её в калькуляторе ниже.',
		'content'  => kedr_seed_blocks(
			[
				[ 'h2', 'Что входит в стоимость' ],
				[
					'ul',
					[
						'Выезд замерщика и 3D-проект',
						'Изготовление на собственном производстве',
						'Доставка и подъём на этаж',
						'Сборка и монтаж, подключение подсветки',
						'Гарантия 5 лет на корпус, фасады и монтаж',
					],
				],
			]
		),
	],
	'o-nas'    => [
		'title'   => 'О мастерской',
		'excerpt' => 'С 2014 года делаем мебель, которую не нужно менять через пять лет.',
		'content' => kedr_seed_blocks(
			[
				[ 'p', 'Мастерская «Кедр» начиналась с двух столяров и небольшого цеха. Сегодня у нас собственное производство, команда из 40 человек и больше 1 400 реализованных проектов — от прихожих до кухонь в загородных домах.' ],
				[ 'h2', 'Как мы работаем' ],
				[ 'p', 'Каждый проект ведёт один менеджер — от замера до монтажа. Вы всегда знаете, на каком этапе заказ, и не пересказываете задачу разным людям.' ],
				[
					'ul',
					[
						'Проектируем в 3D и согласуем каждую деталь до начала производства',
						'Режем и кромим плиты на станках с ЧПУ — точность до 0,1 мм',
						'Собираем изделие в цехе, проверяем и только потом везём к вам',
						'Монтажники — штатные сотрудники, а не случайные подрядчики',
					],
				],
				[ 'quote', [ 'Хорошая мебель незаметна: она просто каждый день делает жизнь удобнее.', 'Основатель мастерской' ] ],
				[ 'h2', 'Материалы' ],
				[ 'p', 'Работаем с плитами класса эмиссии E0,5 и E1, фурнитурой Blum и Hettich. Все материалы сертифицированы — документы покажем по первому запросу.' ],
			]
		),
	],
	'kontakty' => [
		'title'    => 'Контакты',
		'template' => 'page-templates/contacts.php',
		'excerpt'  => 'Приезжайте в шоурум, чтобы посмотреть образцы материалов и фурнитуры вживую, или позвоните — ответим на любые вопросы.',
		'content'  => kedr_seed_blocks(
			[
				[ 'h2', 'Как добраться' ],
				[ 'p', 'От метро — 7 минут пешком. Для гостей есть бесплатная парковка у входа. Перед визитом позвоните, чтобы дизайнер был на месте и подготовил образцы.' ],
				[ 'p', '<em>Это демо-проект: адрес, телефоны и реквизиты вымышлены.</em>' ],
			]
		),
	],
	'politika' => [
		'title'   => 'Политика конфиденциальности',
		'excerpt' => 'Как мы обрабатываем персональные данные, которые вы оставляете на сайте.',
		'content' => kedr_seed_blocks(
			[
				[ 'p', '<strong>Демо-текст.</strong> Перед запуском реального сайта замените его на политику, подготовленную с юристом по требованиям 152-ФЗ.' ],
				[ 'h2', 'Какие данные мы собираем' ],
				[ 'p', 'Имя, номер телефона и текст сообщения, которые вы указываете в форме заявки.' ],
				[ 'h2', 'Зачем' ],
				[ 'p', 'Чтобы связаться с вами, ответить на вопросы и подготовить расчёт стоимости мебели. Данные не передаются третьим лицам и не используются для рассылок.' ],
				[ 'h2', 'Как долго храним' ],
				[ 'p', 'До достижения целей обработки или до отзыва согласия. Чтобы отозвать согласие, напишите нам на почту, указанную в разделе «Контакты».' ],
			]
		),
	],
];

$page_ids = [];
$order    = 0;
foreach ( $pages as $slug => $page ) {
	$args = [
		'post_type'    => 'page',
		'post_name'    => $slug,
		'post_title'   => $page['title'],
		'post_excerpt' => $page['excerpt'],
		'post_content' => $page['content'],
		'menu_order'   => ++$order,
	];
	if ( ! empty( $page['template'] ) ) {
		$args['page_template'] = $page['template'];
	}

	$page_ids[ $slug ] = kedr_seed_post( $args );
}
kedr_seed_say( '  страниц: ' . count( $page_ids ) );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['glavnaya'] );
update_option( 'wp_page_for_privacy_policy', $page_ids['politika'] );
update_option( 'blogname', 'Кедр' );
update_option( 'blogdescription', 'мебель на заказ' );
set_theme_mod( 'demo_link', 'https://github.com/andrewko2025/kedr-wordpress' );

// Стандартные записи свежей установки больше не нужны — убираем в корзину.
foreach ( [ [ 'hello-world', 'post' ], [ 'sample-page', 'page' ], [ 'privacy-policy', 'page' ] ] as [ $slug, $type ] ) {
	$default = get_page_by_path( $slug, OBJECT, $type );
	if ( $default && ! in_array( $default->ID, $page_ids, true ) ) {
		wp_trash_post( $default->ID );
	}
}

/*
 * Меню.
 */

$menus = [
	'primary' => [ 'Главное меню', [ 'uslugi', 'o-nas', 'kontakty' ] ],
	'footer'  => [ 'Меню в подвале', [ 'uslugi', 'o-nas', 'kontakty' ] ],
];

$locations = [];
foreach ( $menus as $location => [ $name, $slugs ] ) {
	$menu    = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	// Пересобираем пункты с нуля, чтобы повторный запуск не плодил дубли.
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		wp_delete_post( $item->ID, true );
	}

	wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-title'  => 'Работы',
			'menu-item-type'   => 'post_type_archive',
			'menu-item-object' => 'project',
			'menu-item-status' => 'publish',
		]
	);

	foreach ( $slugs as $slug ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-object-id' => $page_ids[ $slug ],
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]
		);
	}

	$locations[ $location ] = $menu_id;
}
set_theme_mod( 'nav_menu_locations', $locations );
kedr_seed_say( '  меню: ' . count( $locations ) );

/*
 * Пара демо-заявок, чтобы раздел «Заявки» в админке не был пустым.
 */

if ( ! get_posts( [ 'post_type' => 'lead', 'posts_per_page' => 1, 'fields' => 'ids' ] ) ) {
	kedr_create_lead(
		[
			'name'    => 'Светлана',
			'phone'   => '+7 (900) 000-00-11',
			'message' => 'Нужна угловая кухня примерно 3 × 2 м, интересуют фасады в эмали. Когда можно сделать замер?',
			'source'  => home_url( '/' ),
		]
	);
	kedr_create_lead(
		[
			'name'   => 'Дмитрий',
			'phone'  => '+7 (900) 000-00-22',
			'calc'   => 'Шкаф-купе, 2,4 м; фасады: ЛДСП; фурнитура: Стандартная; оценка: 69 000 – 88 000 ₽',
			'source' => home_url( '/uslugi/' ),
		]
	);
	kedr_create_lead(
		[
			'name'       => 'Анна',
			'phone'      => '+7 (900) 000-00-33',
			'project_id' => $project_ids['detskaya-cherdak'],
			'message'    => 'Хотим похожую кровать, но комната немного меньше.',
			'source'     => get_permalink( $project_ids['detskaya-cherdak'] ),
		]
	);
	kedr_seed_say( '  демо-заявок: 3' );
}

kedr_seed_say( 'Демо-контент готов.', 'success' );
