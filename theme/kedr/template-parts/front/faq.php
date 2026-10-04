<?php
/**
 * Частые вопросы (нативные <details>, работают без JS).
 *
 * @package Kedr
 */

?>
<section class="section section--alt" aria-labelledby="faq-title">
	<div class="container faq-layout">
		<?php
		kedr_section_head(
			[
				'eyebrow' => __( 'Вопросы и ответы', 'kedr' ),
				'title'   => __( 'Частые вопросы', 'kedr' ),
				'text'    => __( 'Не нашли ответ? Позвоните или оставьте заявку — проконсультируем бесплатно.', 'kedr' ),
				'id'      => 'faq-title',
			]
		);
		?>
		<div class="faq" data-reveal>
			<?php foreach ( kedr_faq() as $kedr_index => $kedr_item ) : ?>
				<details <?php echo 0 === $kedr_index ? 'open' : ''; ?>>
					<summary>
						<?php echo esc_html( $kedr_item['q'] ); ?>
						<?php kedr_the_icon( 'plus' ); ?>
					</summary>
					<p><?php echo esc_html( $kedr_item['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
