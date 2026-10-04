<?php
/**
 * Калькулятор предварительной стоимости. Считает main.js по kedr_calc_config().
 *
 * @package Kedr
 */

$kedr_calc   = kedr_calc_config();
$kedr_length = $kedr_calc['length'];

/**
 * Группа радиокнопок-плиток.
 *
 * @param string $name    Имя поля.
 * @param array  $options Варианты: ключ => [label, price?].
 * @param bool   $prices  Показывать цену за метр.
 */
$kedr_choices = function ( string $name, array $options, bool $prices = false ) {
	$first = true;
	foreach ( $options as $key => $option ) {
		?>
		<label class="choice">
			<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $first ); ?>>
			<span>
				<strong><?php echo esc_html( $option['label'] ); ?></strong>
				<?php if ( $prices ) : ?>
					<small>
						<?php
						/* translators: %s: цена за метр */
						echo esc_html( sprintf( __( 'от %s/м', 'kedr' ), kedr_rub( (int) $option['price'] ) ) );
						?>
					</small>
				<?php endif; ?>
			</span>
		</label>
		<?php
		$first = false;
	}
};
?>
<section class="section section--dark calc" id="calc" aria-labelledby="calc-title">
	<div class="container calc__inner">
		<div class="calc__intro" data-reveal>
			<p class="eyebrow"><?php esc_html_e( 'Калькулятор', 'kedr' ); ?></p>
			<h2 id="calc-title"><?php esc_html_e( 'Рассчитайте стоимость за минуту', 'kedr' ); ?></h2>
			<p><?php esc_html_e( 'Выберите тип мебели, размер и материалы — покажем вилку цен по нашему прайсу. Точную смету подготовим после бесплатного замера.', 'kedr' ); ?></p>
			<ul class="check-list">
				<li><?php kedr_the_icon( 'check' ); ?><?php esc_html_e( 'Цена фиксируется в договоре и не меняется', 'kedr' ); ?></li>
				<li><?php kedr_the_icon( 'check' ); ?><?php esc_html_e( '3D-проект бесплатно при заказе', 'kedr' ); ?></li>
				<li><?php kedr_the_icon( 'check' ); ?><?php esc_html_e( 'Рассрочка без переплаты на 6 месяцев', 'kedr' ); ?></li>
			</ul>
		</div>

		<form class="calc__form" data-calc data-reveal>
			<fieldset>
				<legend class="calc__legend"><?php esc_html_e( 'Что нужно изготовить?', 'kedr' ); ?></legend>
				<div class="choice-grid">
					<?php $kedr_choices( 'calc_type', $kedr_calc['types'], true ); ?>
				</div>
			</fieldset>

			<div class="calc__length">
				<div class="calc__length-head">
					<label class="calc__legend" for="calc-length"><?php esc_html_e( 'Длина по стене', 'kedr' ); ?></label>
					<output for="calc-length" data-calc-length-out><?php echo esc_html( number_format( (float) $kedr_length['default'], 1, ',', '' ) ); ?> м</output>
				</div>
				<input
					class="range"
					type="range"
					id="calc-length"
					name="calc_length"
					min="<?php echo esc_attr( $kedr_length['min'] ); ?>"
					max="<?php echo esc_attr( $kedr_length['max'] ); ?>"
					step="<?php echo esc_attr( $kedr_length['step'] ); ?>"
					value="<?php echo esc_attr( $kedr_length['default'] ); ?>"
					data-calc-length
				>
				<div class="range-scale" aria-hidden="true">
					<span><?php echo esc_html( $kedr_length['min'] ); ?> м</span>
					<span><?php echo esc_html( $kedr_length['max'] ); ?> м</span>
				</div>
			</div>

			<fieldset>
				<legend class="calc__legend"><?php esc_html_e( 'Фасады', 'kedr' ); ?></legend>
				<div class="choice-grid choice-grid--compact">
					<?php $kedr_choices( 'calc_material', $kedr_calc['materials'] ); ?>
				</div>
			</fieldset>

			<fieldset>
				<legend class="calc__legend"><?php esc_html_e( 'Фурнитура', 'kedr' ); ?></legend>
				<div class="choice-grid choice-grid--compact">
					<?php $kedr_choices( 'calc_hardware', $kedr_calc['hardware'] ); ?>
				</div>
			</fieldset>

			<fieldset>
				<legend class="calc__legend"><?php esc_html_e( 'Дополнительно', 'kedr' ); ?></legend>
				<div class="checks">
					<?php foreach ( $kedr_calc['extras'] as $kedr_key => $kedr_extra ) : ?>
						<label class="check">
							<input type="checkbox" name="calc_extra[]" value="<?php echo esc_attr( $kedr_key ); ?>">
							<span><?php echo esc_html( $kedr_extra['label'] ); ?> <small>+<?php echo esc_html( kedr_rub( (int) $kedr_extra['price'] ) ); ?></small></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<div class="calc__result">
				<div class="calc__summary">
					<span class="calc__label"><?php esc_html_e( 'Предварительная стоимость', 'kedr' ); ?></span>
					<strong class="calc__price" data-calc-price aria-live="polite">—</strong>
					<span class="calc__note"><?php esc_html_e( 'С изготовлением, доставкой и монтажом', 'kedr' ); ?></span>
				</div>
				<button type="button" class="btn btn--accent" data-calc-submit><?php esc_html_e( 'Получить точный расчёт', 'kedr' ); ?></button>
			</div>
		</form>
	</div>
</section>
