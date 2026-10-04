<?php
/**
 * Этапы работы.
 *
 * @package Kedr
 */

?>
<section class="section section--alt" aria-labelledby="steps-title">
	<div class="container">
		<?php
		kedr_section_head(
			[
				'eyebrow' => __( 'Как мы работаем', 'kedr' ),
				'title'   => __( 'От заявки до готовой мебели', 'kedr' ),
				'text'    => __( 'Один менеджер ведёт проект от замера до монтажа — вам не придётся пересказывать задачу разным людям.', 'kedr' ),
				'id'      => 'steps-title',
			]
		);
		?>
		<ol class="steps">
			<?php foreach ( kedr_steps() as $kedr_step ) : ?>
				<li class="step" data-reveal>
					<h3><?php echo esc_html( $kedr_step['title'] ); ?></h3>
					<p><?php echo esc_html( $kedr_step['text'] ); ?></p>
					<span class="step__time"><?php echo esc_html( $kedr_step['time'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
