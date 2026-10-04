<?php
/**
 * Главная страница. Блоки лежат в template-parts/front/, форма заявки — в подвале.
 *
 * @package Kedr
 */

get_header();

get_template_part( 'template-parts/front/hero' );
get_template_part( 'template-parts/front/categories' );
get_template_part( 'template-parts/front/features' );
get_template_part( 'template-parts/front/projects' );
get_template_part( 'template-parts/front/steps' );
get_template_part( 'template-parts/calculator' );
get_template_part( 'template-parts/front/reviews' );
get_template_part( 'template-parts/front/faq' );

get_footer();
