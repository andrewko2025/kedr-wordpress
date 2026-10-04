<?php
/**
 * Секция с формой заявки. Подключается в подвале на всех страницах.
 * На странице проекта форма запоминает, какой проект понравился.
 *
 * @package Kedr
 */

$kedr_project = is_singular( 'project' ) ? get_queried_object_id() : 0;
$kedr_status  = isset( $_GET['lead'] ) ? sanitize_key( wp_unslash( $_GET['lead'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- только статус после редиректа.
$kedr_sent    = 'ok' === $kedr_status;
$kedr_phone   = kedr_opt( 'phone' );
$kedr_email   = sanitize_email( kedr_opt( 'email' ) );
$kedr_privacy = get_privacy_policy_url();
?>
<section class="section lead" id="lead" aria-labelledby="lead-title">
	<div class="container lead__inner">
		<div class="lead__intro" data-reveal>
			<p class="eyebrow"><?php esc_html_e( 'Бесплатный замер', 'kedr' ); ?></p>
			<h2 id="lead-title">
				<?php
				echo $kedr_project
					? esc_html__( 'Хотите такой же проект?', 'kedr' )
					: esc_html__( 'Обсудим вашу будущую мебель?', 'kedr' );
				?>
			</h2>
			<p class="lead__text"><?php esc_html_e( 'Оставьте телефон — дизайнер перезвонит в течение 15 минут в рабочее время, ответит на вопросы и запишет на замер.', 'kedr' ); ?></p>

			<ul class="lead__contacts">
				<li>
					<a href="<?php echo esc_url( kedr_phone_href( $kedr_phone ) ); ?>">
						<span class="lead__icon"><?php kedr_the_icon( 'phone' ); ?></span>
						<span><strong><?php echo esc_html( $kedr_phone ); ?></strong><small><?php echo esc_html( kedr_opt( 'hours' ) ); ?></small></span>
					</a>
				</li>
				<?php if ( $kedr_email ) : ?>
					<li>
						<a href="mailto:<?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
							<span class="lead__icon"><?php kedr_the_icon( 'mail' ); ?></span>
							<span><strong><?php echo antispambot( $kedr_email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><small><?php esc_html_e( 'Ответим в течение дня', 'kedr' ); ?></small></span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</div>

		<form class="lead-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-lead-form data-reveal>
			<div class="lead-form__fields" data-form-fields<?php echo $kedr_sent ? ' hidden' : ''; ?>>
				<h3 class="lead-form__title"><?php esc_html_e( 'Заявка на замер', 'kedr' ); ?></h3>

				<input type="hidden" name="action" value="kedr_lead">
				<input type="hidden" name="project_id" value="<?php echo esc_attr( $kedr_project ); ?>">
				<input type="hidden" name="calc" value="" data-lead-calc>
				<?php wp_nonce_field( 'kedr_lead', 'kedr_lead_nonce', false ); ?>

				<div class="hp-field" aria-hidden="true">
					<label for="lead-website"><?php esc_html_e( 'Не заполняйте это поле', 'kedr' ); ?></label>
					<input type="text" id="lead-website" name="website" tabindex="-1" autocomplete="off">
				</div>

				<div class="field-row">
					<div class="field">
						<label for="lead-name"><?php esc_html_e( 'Ваше имя', 'kedr' ); ?></label>
						<input class="input" type="text" id="lead-name" name="name" autocomplete="name" minlength="2" maxlength="80" required>
					</div>
					<div class="field">
						<label for="lead-phone"><?php esc_html_e( 'Телефон', 'kedr' ); ?></label>
						<input class="input" type="tel" id="lead-phone" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required>
					</div>
				</div>

				<div class="field">
					<label for="lead-message">
						<?php esc_html_e( 'Что нужно сделать?', 'kedr' ); ?>
						<span class="field__optional"><?php esc_html_e( 'необязательно', 'kedr' ); ?></span>
					</label>
					<textarea class="input" id="lead-message" name="message" rows="4" maxlength="2000" placeholder="<?php esc_attr_e( 'Например: угловая кухня 3 × 2 м, фасады без ручек', 'kedr' ); ?>" data-lead-message></textarea>
				</div>

				<label class="consent">
					<input type="checkbox" name="consent" value="1" required>
					<span>
						<?php if ( $kedr_privacy ) : ?>
							<?php
							printf(
								/* translators: %s: ссылка на политику конфиденциальности */
								esc_html__( 'Соглашаюсь на обработку персональных данных согласно %s', 'kedr' ),
								'<a href="' . esc_url( $kedr_privacy ) . '">' . esc_html__( 'политике конфиденциальности', 'kedr' ) . '</a>'
							);
							?>
						<?php else : ?>
							<?php esc_html_e( 'Соглашаюсь на обработку персональных данных', 'kedr' ); ?>
						<?php endif; ?>
					</span>
				</label>

				<button class="btn btn--accent btn--lg btn--block" type="submit"><?php esc_html_e( 'Отправить заявку', 'kedr' ); ?></button>

				<?php // Без пробелов внутри <p>: пустой статус схлопывается через :empty. ?>
				<p class="form-status<?php echo 'error' === $kedr_status ? ' is-error' : ''; ?>" role="status" data-form-status><?php echo 'error' === $kedr_status ? esc_html__( 'Не удалось отправить заявку. Проверьте поля или позвоните нам.', 'kedr' ) : ''; ?></p>
			</div>

			<div class="lead-form__success" tabindex="-1" data-form-success<?php echo $kedr_sent ? '' : ' hidden'; ?>>
				<span class="lead-form__success-icon"><?php kedr_the_icon( 'check' ); ?></span>
				<h3><?php esc_html_e( 'Заявка отправлена!', 'kedr' ); ?></h3>
				<p><?php esc_html_e( 'Спасибо! Перезвоним в течение 15 минут в рабочее время.', 'kedr' ); ?></p>
			</div>
		</form>
	</div>
</section>
