/**
 * Кедр — выбор фото для галереи проекта через медиатеку WordPress.
 */
document.addEventListener('DOMContentLoaded', () => {
	'use strict';

	const box = document.querySelector('[data-kedr-gallery]');
	if (!box || !window.wp?.media) return;

	const input = box.querySelector('input[name="kedr_gallery"]');
	const list = box.querySelector('[data-kedr-gallery-list]');
	let frame = null;

	const render = (attachments) => {
		list.innerHTML = '';
		attachments.forEach((attachment) => {
			const img = document.createElement('img');
			img.src = attachment.sizes?.thumbnail?.url || attachment.url;
			img.alt = '';
			list.append(img);
		});
	};

	box.querySelector('[data-kedr-gallery-pick]').addEventListener('click', (event) => {
		event.preventDefault();

		if (!frame) {
			frame = wp.media({
				title: 'Галерея проекта',
				button: { text: 'Использовать выбранные' },
				library: { type: 'image' },
				multiple: 'add',
			});

			// При открытии отмечаем уже выбранные фото.
			frame.on('open', () => {
				const selection = frame.state().get('selection');
				selection.reset();
				input.value
					.split(',')
					.filter(Boolean)
					.forEach((id) => {
						const attachment = wp.media.attachment(id);
						attachment.fetch();
						selection.add(attachment);
					});
			});

			frame.on('select', () => {
				const attachments = frame.state().get('selection').toJSON();
				input.value = attachments.map((attachment) => attachment.id).join(',');
				render(attachments);
			});
		}

		frame.open();
	});

	box.querySelector('[data-kedr-gallery-clear]').addEventListener('click', (event) => {
		event.preventDefault();
		input.value = '';
		list.innerHTML = '';
	});
});
