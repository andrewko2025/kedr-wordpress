/**
 * Кедр — интерактив сайта: шапка, мобильное меню, калькулятор,
 * отправка заявки без перезагрузки, лайтбокс галереи, появление блоков.
 * Без зависимостей.
 */
(() => {
	'use strict';

	const data = window.kedrData || {};
	const $ = (selector, root = document) => root.querySelector(selector);
	const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	const rub = (value) => `${new Intl.NumberFormat('ru-RU').format(value)} ₽`;

	const svg = (paths) =>
		`<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;

	/* Шапка: тень после начала прокрутки
	   ---------------------------------------------------------------------- */

	const header = $('[data-header]');
	if (header) {
		const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	/* Мобильное меню
	   ---------------------------------------------------------------------- */

	const burger = $('[data-burger]');
	const nav = $('[data-nav]');

	if (burger && nav) {
		const setOpen = (open) => {
			burger.setAttribute('aria-expanded', String(open));
			burger.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
			nav.classList.toggle('is-open', open);
			document.body.classList.toggle('is-locked', open);
		};

		burger.addEventListener('click', () => setOpen(burger.getAttribute('aria-expanded') !== 'true'));
		nav.addEventListener('click', (event) => {
			if (event.target.closest('a')) setOpen(false);
		});
		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape' && nav.classList.contains('is-open')) {
				setOpen(false);
				burger.focus();
			}
		});
		window.matchMedia('(min-width: 1000px)').addEventListener('change', (event) => {
			if (event.matches) setOpen(false);
		});
	}

	/* Калькулятор
	   ---------------------------------------------------------------------- */

	const calc = $('[data-calc]');

	if (calc && data.calc) {
		const cfg = data.calc;
		const range = $('[data-calc-length]', calc);
		const lengthOut = $('[data-calc-length-out]', calc);
		const priceOut = $('[data-calc-price]', calc);
		const checked = (name) => calc.querySelector(`input[name="${name}"]:checked`)?.value;
		const roundTo1000 = (value) => Math.round(value / 1000) * 1000;
		const formatLength = (value) =>
			`${value.toLocaleString('ru-RU', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} м`;

		let summary = '';

		const update = () => {
			const type = cfg.types[checked('calc_type')];
			const material = cfg.materials[checked('calc_material')];
			const hardware = cfg.hardware[checked('calc_hardware')];
			const extras = $$('input[name="calc_extra[]"]:checked', calc)
				.map((input) => cfg.extras[input.value])
				.filter(Boolean);
			const length = parseFloat(range.value);

			if (!type || !material || !hardware) return;

			const base =
				type.price * length * material.k * hardware.k + extras.reduce((sum, extra) => sum + extra.price, 0);
			const [low, high] = cfg.spread;
			const from = roundTo1000(base * low);
			const to = roundTo1000(base * high);
			const fill = ((range.value - range.min) / (range.max - range.min)) * 100;

			priceOut.textContent = `${new Intl.NumberFormat('ru-RU').format(from)} – ${rub(to)}`;
			lengthOut.textContent = formatLength(length);
			range.style.setProperty('--fill', `${fill}%`);

			summary = [
				`${type.label}, ${formatLength(length)}`,
				`фасады: ${material.label}`,
				`фурнитура: ${hardware.label}`,
				...extras.map((extra) => extra.label),
				`оценка: ${priceOut.textContent}`,
			].join('; ');
		};

		calc.addEventListener('input', update);
		calc.addEventListener('change', update);
		calc.addEventListener('submit', (event) => event.preventDefault());
		update();

		$('[data-calc-submit]', calc)?.addEventListener('click', () => {
			const calcField = $('[data-lead-calc]');
			const message = $('[data-lead-message]');
			const lead = $('#lead');

			if (calcField) calcField.value = summary;
			if (message && !message.value.trim()) {
				message.value = `Здравствуйте! Хочу уточнить расчёт: ${summary}.`;
			}
			if (lead) {
				lead.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth' });
				window.setTimeout(() => $('#lead-name')?.focus({ preventScroll: true }), reducedMotion.matches ? 0 : 600);
			}
		});
	}

	/* Форма заявки
	   ---------------------------------------------------------------------- */

	// +7 (999) 123-45-67. Скобка закрывается только когда начат следующий блок,
	// иначе Backspace «упирается» в неё.
	const formatPhone = (raw) => {
		let digits = raw.replace(/\D/g, '');
		if (!digits) return '';
		if (digits[0] === '8') digits = `7${digits.slice(1)}`;
		if (digits[0] !== '7') digits = `7${digits}`;
		digits = digits.slice(0, 11);

		const [code, a, b, c] = [digits.slice(1, 4), digits.slice(4, 7), digits.slice(7, 9), digits.slice(9, 11)];
		let out = '+7';
		if (code) out += ` (${code}`;
		if (a) out += `) ${a}`;
		if (b) out += `-${b}`;
		if (c) out += `-${c}`;
		return out;
	};

	$$('[data-lead-form]').forEach((form) => {
		const fields = $('[data-form-fields]', form);
		const success = $('[data-form-success]', form);
		const status = $('[data-form-status]', form);
		const button = $('button[type="submit"]', form);
		const phone = $('input[name="phone"]', form);
		const fallbackError = 'Не удалось отправить заявку. Позвоните нам, пожалуйста.';
		// data-demo ставит скрипт статической копии (scripts/export-static.mjs): на GitHub Pages
		// нет PHP, поэтому заявка не отправляется, а посетитель видит пояснение.
		const isDemo = form.hasAttribute('data-demo');

		// Валидируем сами, чтобы показывать понятные сообщения.
		form.noValidate = true;

		phone?.addEventListener('input', () => {
			phone.value = formatPhone(phone.value);
		});

		const setStatus = (text, isError = false) => {
			status.textContent = text;
			status.classList.toggle('is-error', isError);
		};

		const validate = () => {
			const rules = {
				name: (input) => input.value.trim().length >= 2,
				phone: (input) => input.value.replace(/\D/g, '').length === 11,
				consent: (input) => input.checked,
			};
			let firstInvalid = null;

			Object.entries(rules).forEach(([name, isValid]) => {
				const input = form.elements[name];
				if (!input) return;
				const valid = isValid(input);
				input.setAttribute('aria-invalid', String(!valid));
				if (!valid && !firstInvalid) firstInvalid = input;
			});

			firstInvalid?.focus();
			return !firstInvalid;
		};

		const showSuccess = (title, text) => {
			if (title) $('h3', success).textContent = title;
			if (text) $('p', success).textContent = text;
			form.reset();
			setStatus('');
			fields.hidden = true;
			success.hidden = false;
			success.focus();
		};

		form.addEventListener('input', (event) => {
			if (event.target.getAttribute('aria-invalid') === 'true') event.target.removeAttribute('aria-invalid');
		});

		form.addEventListener('submit', async (event) => {
			event.preventDefault();

			if (!validate()) {
				setStatus('Заполните имя, телефон и отметьте согласие.', true);
				return;
			}

			if (isDemo) {
				showSuccess(
					'Это демо-версия сайта',
					'Заявка никуда не отправлена. На рабочем сайте она сохраняется в разделе «Заявки» в админке WordPress и приходит на почту.'
				);
				return;
			}

			button.disabled = true;
			setStatus('Отправляем…');

			let message = '';
			try {
				// Не form.action: скрытое поле name="action" перекрывает это свойство.
				const response = await fetch(form.getAttribute('action'), {
					method: 'POST',
					body: new FormData(form),
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
				});
				const json = await response.json().catch(() => null);

				if (response.ok && json?.success) {
					showSuccess();
					return;
				}

				message = json?.data?.message || '';
			} catch (error) {
				message = '';
			} finally {
				button.disabled = false;
			}

			setStatus(message || fallbackError, true);
		});
	});

	/* Лайтбокс галереи проекта
	   ---------------------------------------------------------------------- */

	const galleryLinks = $$('[data-lightbox]');

	if (galleryLinks.length) {
		const box = document.createElement('div');
		box.className = 'lightbox';
		box.hidden = true;
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'Просмотр фотографий');
		box.innerHTML = `
			<img class="lightbox__img" alt="">
			<button type="button" class="lightbox__btn lightbox__close" aria-label="Закрыть">${svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>')}</button>
			<button type="button" class="lightbox__btn lightbox__prev" aria-label="Предыдущее фото">${svg('<path d="m15 18-6-6 6-6"/>')}</button>
			<button type="button" class="lightbox__btn lightbox__next" aria-label="Следующее фото">${svg('<path d="m9 18 6-6-6-6"/>')}</button>
			<p class="lightbox__count" aria-live="polite"></p>`;
		document.body.append(box);

		const image = $('.lightbox__img', box);
		const counter = $('.lightbox__count', box);
		const buttons = $$('.lightbox__btn', box);
		let group = [];
		let index = 0;
		let lastFocus = null;

		const show = (next) => {
			index = (next + group.length) % group.length;
			const link = group[index];
			image.src = link.href;
			image.alt = link.querySelector('img')?.alt || '';
			counter.textContent = `${index + 1} / ${group.length}`;
		};

		const open = (link) => {
			group = galleryLinks.filter((item) => item.dataset.lightbox === link.dataset.lightbox);
			lastFocus = document.activeElement;
			box.classList.toggle('is-single', group.length < 2);
			show(group.indexOf(link));
			box.hidden = false;
			document.body.classList.add('is-locked');
			buttons[0].focus();
		};

		const close = () => {
			box.hidden = true;
			image.removeAttribute('src');
			document.body.classList.remove('is-locked');
			lastFocus?.focus();
		};

		galleryLinks.forEach((link) =>
			link.addEventListener('click', (event) => {
				event.preventDefault();
				open(link);
			})
		);

		box.addEventListener('click', (event) => {
			if (event.target === box || event.target.closest('.lightbox__close')) close();
			else if (event.target.closest('.lightbox__prev')) show(index - 1);
			else if (event.target.closest('.lightbox__next')) show(index + 1);
		});

		document.addEventListener('keydown', (event) => {
			if (box.hidden) return;

			if (event.key === 'Escape') close();
			if (event.key === 'ArrowLeft') show(index - 1);
			if (event.key === 'ArrowRight') show(index + 1);

			// Фокус не уходит из открытого окна.
			if (event.key === 'Tab') {
				const visible = buttons.filter((button) => button.offsetParent !== null);
				const first = visible[0];
				const last = visible[visible.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			}
		});
	}

	/* Плавное появление блоков при прокрутке
	   ---------------------------------------------------------------------- */

	const reveals = $$('[data-reveal]');

	if ('IntersectionObserver' in window && !reducedMotion.matches) {
		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (!entry.isIntersecting) return;
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
		);

		// Соседние карточки появляются по очереди.
		reveals.forEach((element) => {
			const siblings = $$(':scope > [data-reveal]', element.parentElement);
			const position = siblings.indexOf(element);
			if (position > 0) element.style.transitionDelay = `${Math.min(position, 5) * 70}ms`;
			observer.observe(element);
		});
	} else {
		reveals.forEach((element) => element.classList.add('is-visible'));
	}
})();
