// Скриншоты сайта для портфолио: node scripts/screenshots.mjs [адрес сайта]
//
// Нужен установленный Google Chrome или Microsoft Edge (путь можно задать в CHROME_PATH).
// Без npm-зависимостей: браузер запускается в headless-режиме и управляется
// напрямую по Chrome DevTools Protocol. Результат — в папке screenshots/.
// Для снимков админки скрипт входит в WordPress с логином и паролем из .env.

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { setTimeout as sleep } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

const BROWSERS = [
	process.env.CHROME_PATH,
	'C:/Program Files/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
	'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
	'/usr/bin/google-chrome',
	'/usr/bin/chromium',
].filter(Boolean);

const ROOT = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');

export const DESKTOP = { width: 1440, height: 900, scale: 1, mobile: false };
export const MOBILE = { width: 390, height: 844, scale: 2, mobile: true };

/** Адрес редактирования работы по её slug (ID берём из REST API). */
const editProject = (slug) => async (baseUrl) => {
	const [project] = await (await fetch(`${baseUrl}/wp-json/wp/v2/project?slug=${slug}`)).json();
	return `/wp-admin/post.php?post=${project.id}&action=edit`;
};

// Закрываем приветственное окно редактора блоков и раскрываем панель с полями проекта.
const showMetaBox = `(async () => {
	wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
	const toggle = [...document.querySelectorAll('button[aria-expanded="false"]')]
		.find((button) => /Мета-бокс|Meta Boxes/i.test(button.textContent));
	toggle?.click();
	await new Promise((done) => setTimeout(done, 800));
	document.querySelector('#kedr-project-details')?.scrollIntoView({ block: 'start' });
})()`;

const SHOTS = [
	{ name: '01-home-hero', path: '/', device: DESKTOP },
	{ name: '02-home-full', path: '/', device: DESKTOP, fullPage: true },
	{ name: '03-catalog', path: '/raboty/', device: DESKTOP, fullPage: true },
	{ name: '04-project', path: '/raboty/kuhnya-skandi/', device: DESKTOP, fullPage: true },
	{ name: '05-services', path: '/uslugi/', device: DESKTOP, fullPage: true },
	{ name: '06-calculator', path: '/', device: DESKTOP, selector: '#calc' },
	{ name: '07-mobile-home', path: '/', device: MOBILE },
	{ name: '08-mobile-menu', path: '/', device: MOBILE, before: "document.querySelector('[data-burger]').click()" },
	{ name: '09-mobile-project', path: '/raboty/kuhnya-skandi/', device: MOBILE },
	{ name: '10-mobile-full', path: '/', device: { ...MOBILE, scale: 1 }, fullPage: true },
	{ name: '11-admin-leads', path: '/wp-admin/edit.php?post_type=lead', device: DESKTOP, admin: true },
	{ name: '12-admin-projects', path: '/wp-admin/edit.php?post_type=project', device: DESKTOP, admin: true },
	{ name: '13-admin-project-fields', path: editProject('kuhnya-skandi'), device: DESKTOP, admin: true, wait: 3000, before: showMetaBox },
];

/** Переменные из .env проекта. */
async function readEnv() {
	const text = await readFile(path.join(ROOT, '.env'), 'utf8').catch(() => '');
	return Object.fromEntries(
		text
			.split(/\r?\n/)
			.filter((line) => line.includes('=') && !line.trimStart().startsWith('#'))
			.map((line) => [line.slice(0, line.indexOf('=')).trim(), line.slice(line.indexOf('=') + 1).trim()])
	);
}

/** Входит в WordPress и возвращает cookies сессии в формате CDP. */
async function wpLogin(baseUrl) {
	const { WP_ADMIN_USER, WP_ADMIN_PASSWORD } = await readEnv();
	if (!WP_ADMIN_USER || !WP_ADMIN_PASSWORD) throw new Error('Нет WP_ADMIN_USER / WP_ADMIN_PASSWORD в .env');

	const response = await fetch(`${baseUrl}/wp-login.php`, {
		method: 'POST',
		redirect: 'manual',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Cookie: 'wordpress_test_cookie=WP%20Cookie%20check',
		},
		body: new URLSearchParams({ log: WP_ADMIN_USER, pwd: WP_ADMIN_PASSWORD, testcookie: '1' }),
	});

	const cookies = response.headers
		.getSetCookie()
		.map((header) => header.split(';')[0])
		.map((pair) => ({ name: pair.slice(0, pair.indexOf('=')), value: pair.slice(pair.indexOf('=') + 1), url: baseUrl }))
		.filter((cookie) => cookie.name.startsWith('wordpress_') && cookie.value !== 'deleted');

	if (!cookies.some((cookie) => cookie.name.startsWith('wordpress_logged_in_'))) {
		throw new Error('Не удалось войти в админку: проверьте логин и пароль в .env');
	}
	return cookies;
}

/** Минимальный клиент Chrome DevTools Protocol поверх встроенного WebSocket. */
class CDP {
	#ws;
	#id = 0;
	#pending = new Map();
	#listeners = new Map();

	constructor(ws) {
		this.#ws = ws;
		ws.addEventListener('message', ({ data }) => {
			const message = JSON.parse(data);
			if (message.id) {
				const { resolve, reject } = this.#pending.get(message.id);
				this.#pending.delete(message.id);
				message.error ? reject(new Error(message.error.message)) : resolve(message.result);
			} else {
				(this.#listeners.get(message.method) || []).forEach((listener) => listener(message.params));
			}
		});
	}

	static async connect(url) {
		const ws = new WebSocket(url);
		await new Promise((resolve, reject) => {
			ws.addEventListener('open', resolve, { once: true });
			ws.addEventListener('error', reject, { once: true });
		});
		return new CDP(ws);
	}

	send(method, params = {}) {
		const id = ++this.#id;
		this.#ws.send(JSON.stringify({ id, method, params }));
		return new Promise((resolve, reject) => this.#pending.set(id, { resolve, reject }));
	}

	once(method) {
		return new Promise((resolve) => {
			const listener = (params) => {
				this.#listeners.set(method, this.#listeners.get(method).filter((item) => item !== listener));
				resolve(params);
			};
			this.#listeners.set(method, [...(this.#listeners.get(method) || []), listener]);
		});
	}

	async evaluate(expression) {
		const { result, exceptionDetails } = await this.send('Runtime.evaluate', {
			expression,
			awaitPromise: true,
			returnByValue: true,
		});
		if (exceptionDetails) throw new Error(exceptionDetails.exception?.description || exceptionDetails.text);
		return result.value;
	}

	close() {
		this.#ws.close();
	}
}

/**
 * Снимает список скриншотов.
 *
 * @param {Array<{name: string, path: string|Function, device: object, fullPage?: boolean, selector?: string, before?: string, wait?: number, admin?: boolean}>} shots
 * @param {string} outDir Куда сохранить JPEG.
 * @param {string} baseUrl Адрес сайта.
 */
export async function capture(shots, outDir, baseUrl) {
	const browser = BROWSERS.find((candidate) => existsSync(candidate));
	if (!browser) throw new Error('Не найден Chrome или Edge. Укажите путь в переменной CHROME_PATH.');

	await mkdir(outDir, { recursive: true });
	const profile = await mkdtemp(path.join(tmpdir(), 'kedr-shots-'));
	const chrome = spawn(
		browser,
		[
			'--headless=new',
			'--hide-scrollbars',
			'--no-first-run',
			'--no-default-browser-check',
			'--remote-debugging-port=0',
			`--user-data-dir=${profile}`,
			'about:blank',
		],
		{ stdio: 'ignore' }
	);

	try {
		// Порт отладки браузер записывает в файл DevToolsActivePort внутри профиля.
		let port = '';
		for (let i = 0; i < 100 && !port; i++) {
			port = await readFile(path.join(profile, 'DevToolsActivePort'), 'utf8')
				.then((text) => text.split('\n')[0])
				.catch(() => sleep(100).then(() => ''));
		}
		if (!port) throw new Error('Браузер не запустился.');

		const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
		const cdp = await CDP.connect(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
		await cdp.send('Page.enable');

		// Входим в админку только для её снимков: на публичных страницах не должно быть панели администратора.
		const adminCookies = shots.some((shot) => shot.admin) ? await wpLogin(baseUrl) : [];
		let loggedIn = false;

		for (const shot of shots) {
			if (Boolean(shot.admin) !== loggedIn) {
				await cdp.send(shot.admin ? 'Network.setCookies' : 'Network.clearBrowserCookies', shot.admin ? { cookies: adminCookies } : {});
				loggedIn = Boolean(shot.admin);
			}

			const { width, height, scale, mobile } = shot.device;
			await cdp.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: scale, mobile });
			await cdp.send('Emulation.setTouchEmulationEnabled', { enabled: mobile });

			const url = baseUrl + (typeof shot.path === 'function' ? await shot.path(baseUrl) : shot.path);
			const loaded = cdp.once('Page.loadEventFired');
			await cdp.send('Page.navigate', { url });
			await loaded;

			// Анимации появления срабатывают при прокрутке, ленивые картинки — тоже.
			// На скриншоте показываем всё сразу.
			await cdp.evaluate(`(async () => {
				document.documentElement.classList.remove('js');
				document.querySelectorAll('img[loading="lazy"]').forEach((img) => { img.loading = 'eager'; });
				await document.fonts.ready;
				await Promise.all([...document.images].map((img) => img.complete ? null : new Promise((done) => { img.onload = img.onerror = done; })));
			})()`);
			await sleep(shot.wait || 0);
			if (shot.before) await cdp.evaluate(shot.before);
			await sleep(700);

			let clip;
			if (shot.fullPage) {
				const { cssContentSize } = await cdp.send('Page.getLayoutMetrics');
				clip = { x: 0, y: 0, width, height: Math.ceil(cssContentSize.height), scale: 1 };
			} else if (shot.selector) {
				const box = await cdp.evaluate(`(() => {
					const rect = document.querySelector(${JSON.stringify(shot.selector)}).getBoundingClientRect();
					return { x: rect.left + scrollX, y: rect.top + scrollY, width: rect.width, height: rect.height };
				})()`);
				clip = { ...box, scale: 1 };
			}

			const { data } = await cdp.send('Page.captureScreenshot', {
				format: 'jpeg',
				quality: 88,
				...(clip && { clip, captureBeyondViewport: true }),
			});
			const file = path.join(outDir, `${shot.name}.jpg`);
			await writeFile(file, Buffer.from(data, 'base64'));
			console.log(`✓ ${path.relative(process.cwd(), file)}`);
		}

		cdp.close();
	} finally {
		chrome.kill();
		await sleep(500);
		await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 300 }).catch(() => {});
	}
}

// Запуск из командной строки.
if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
	const base = (process.argv[2] || 'http://localhost:8080').replace(/\/$/, '');
	const out = path.join(ROOT, 'screenshots');
	// Необязательный фильтр по имени: node scripts/screenshots.mjs http://localhost:8080 admin
	const only = process.argv[3] ? new RegExp(process.argv[3]) : null;

	capture(SHOTS.filter((shot) => !only || only.test(shot.name)), out, base).catch((error) => {
		console.error(error.message);
		process.exit(1);
	});
}
