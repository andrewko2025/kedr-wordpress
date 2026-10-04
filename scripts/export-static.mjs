// Статическая копия сайта для GitHub Pages:
//   node scripts/export-static.mjs https://andrewko2025.github.io/kedr-wordpress
//
// Обходит локальный сайт (http://localhost:8080, другой — в SOURCE_URL), сохраняет страницы,
// стили, скрипты и картинки в docs/ и заменяет локальный адрес на публичный.
// Форма заявки в копии работает в демо-режиме (атрибут data-demo, см. assets/js/main.js).
// Заодно собирает docs/kedr.zip — архив темы для установки и для WordPress Playground.
// Без npm-зависимостей.

import { mkdir, readdir, readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { crc32, deflateRawSync } from 'node:zlib';

const ROOT = path.join(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(ROOT, 'docs');
const SOURCE = (process.env.SOURCE_URL || 'http://localhost:8080').replace(/\/$/, '');
const TARGET = (process.argv[2] || '').replace(/\/$/, '');

// Служебные адреса WordPress, которым нечего делать в статической копии.
const SKIP = /\/wp-admin\/|\/wp-json\/|\/feed\/|\/comments\/|xmlrpc|\.php(?:$|\?)|[?&](?:p|s|page_id|replytocom)=/;
const TEXT = /text\/|javascript|json|xml|svg/;

/** Все ссылки на ресурсы этого же сайта внутри HTML или CSS. */
function collectLinks(text, base) {
	const refs = [];
	for (const [, attr, value] of text.matchAll(/\b(href|src|action|content)=["']([^"']+)["']/g)) {
		// В content бывает и обычный текст (meta description) — берём только адреса.
		if (attr !== 'content' || /^https?:/.test(value)) refs.push(value);
	}
	for (const [, value] of text.matchAll(/\bsrcset=["']([^"']+)["']/g)) {
		refs.push(...value.split(',').map((candidate) => candidate.trim().split(/\s+/)[0]));
	}
	for (const [, value] of text.matchAll(/url\(\s*['"]?([^'")]+)['"]?\s*\)/g)) refs.push(value);

	return refs
		.map((ref) => {
			try {
				return new URL(ref.replaceAll('&#038;', '&').replaceAll('&amp;', '&'), base);
			} catch {
				return null;
			}
		})
		.filter((url) => url && url.origin === new URL(SOURCE).origin && !SKIP.test(url.pathname + url.search));
}

/** Путь файла в docs/: страницы — как папка с index.html, остальное — как есть. */
function outputFile(url, isHtml) {
	let file = decodeURIComponent(url.pathname);
	if (isHtml && !path.extname(file)) file = path.posix.join(file, 'index.html');
	return path.join(OUT, file);
}

/** Локальный адрес → публичный, в том числе в экранированном JSON (http:\/\/…). */
function rewrite(text) {
	return text.replaceAll(SOURCE, TARGET).replaceAll(SOURCE.replaceAll('/', '\\/'), TARGET.replaceAll('/', '\\/'));
}

async function save(file, data) {
	await mkdir(path.dirname(file), { recursive: true });
	await writeFile(file, data);
}

/** Обход сайта в ширину, начиная с главной. */
async function crawl() {
	const queue = [new URL(`${SOURCE}/`)];
	const seen = new Set([queue[0].pathname]);
	let pages = 0;
	let assets = 0;

	const visit = async (url, file) => {
		const response = await fetch(url);
		const type = response.headers.get('content-type') || '';
		const isHtml = type.includes('text/html');

		if (!response.ok && !file) {
			console.warn(`! ${response.status} ${url.pathname}`);
			return;
		}

		if (!TEXT.test(type)) {
			await save(file || outputFile(url, false), Buffer.from(await response.arrayBuffer()));
			assets++;
			return;
		}

		let text = await response.text();
		for (const link of collectLinks(text, url)) {
			if (!seen.has(link.pathname)) {
				seen.add(link.pathname);
				queue.push(new URL(link.pathname, SOURCE));
			}
		}

		text = rewrite(text);
		if (isHtml) {
			text = text.replaceAll('data-lead-form', 'data-lead-form data-demo');
			pages++;
		} else {
			assets++;
		}
		await save(file || outputFile(url, isHtml), text);
	};

	// Страница 404 для GitHub Pages: отдаётся на любой несуществующий адрес.
	// Её адрес помечаем посещённым, иначе якоря вроде #main уведут обход на неё же.
	const notFound = new URL(`${SOURCE}/kedr-static-404/`);
	seen.add(notFound.pathname);
	await visit(notFound, path.join(OUT, '404.html'));

	while (queue.length) {
		await visit(queue.shift());
	}

	return { pages, assets };
}

/** Минимальный ZIP (deflate) без зависимостей. */
function zip(entries) {
	const local = [];
	const central = [];
	let offset = 0;

	for (const { name, data } of entries) {
		const nameBuffer = Buffer.from(name, 'utf8');
		const packed = deflateRawSync(data);
		const checksum = crc32(data);

		const header = Buffer.alloc(30);
		header.writeUInt32LE(0x04034b50, 0); // сигнатура локального заголовка
		header.writeUInt16LE(20, 4); // версия для распаковки
		header.writeUInt16LE(0x0800, 6); // имена в UTF-8
		header.writeUInt16LE(8, 8); // deflate
		header.writeUInt16LE(0x21, 12); // дата 01.01.1980
		header.writeUInt32LE(checksum, 14);
		header.writeUInt32LE(packed.length, 18);
		header.writeUInt32LE(data.length, 22);
		header.writeUInt16LE(nameBuffer.length, 26);
		local.push(header, nameBuffer, packed);

		const record = Buffer.alloc(46);
		record.writeUInt32LE(0x02014b50, 0); // сигнатура центрального каталога
		record.writeUInt16LE(20, 4);
		record.writeUInt16LE(20, 6);
		record.writeUInt16LE(0x0800, 8);
		record.writeUInt16LE(8, 10);
		record.writeUInt16LE(0x21, 14);
		record.writeUInt32LE(checksum, 16);
		record.writeUInt32LE(packed.length, 20);
		record.writeUInt32LE(data.length, 24);
		record.writeUInt16LE(nameBuffer.length, 28);
		record.writeUInt32LE(offset, 42);
		central.push(record, nameBuffer);

		offset += header.length + nameBuffer.length + packed.length;
	}

	const centralSize = central.reduce((sum, buffer) => sum + buffer.length, 0);
	const end = Buffer.alloc(22);
	end.writeUInt32LE(0x06054b50, 0); // конец центрального каталога
	end.writeUInt16LE(entries.length, 8);
	end.writeUInt16LE(entries.length, 10);
	end.writeUInt32LE(centralSize, 12);
	end.writeUInt32LE(offset, 16);

	return Buffer.concat([...local, ...central, end]);
}

/** Файлы темы для архива: kedr/… с прямыми слешами. */
async function themeEntries(dir = path.join(ROOT, 'theme', 'kedr'), prefix = 'kedr') {
	const entries = [];
	for (const item of await readdir(dir, { withFileTypes: true })) {
		const full = path.join(dir, item.name);
		const name = `${prefix}/${item.name}`;
		if (item.isDirectory()) entries.push(...(await themeEntries(full, name)));
		else entries.push({ name, data: await readFile(full) });
	}
	return entries;
}

if (!TARGET) {
	console.error('Укажите публичный адрес: node scripts/export-static.mjs https://<логин>.github.io/<репозиторий>');
	process.exit(1);
}

await rm(OUT, { recursive: true, force: true });
const { pages, assets } = await crawl();
await writeFile(path.join(OUT, '.nojekyll'), ''); // без Jekyll: публикуем файлы как есть
const theme = await themeEntries();
await writeFile(path.join(OUT, 'kedr.zip'), zip(theme));

console.log(`✓ docs/: ${pages} страниц, ${assets} файлов; kedr.zip — ${theme.length} файлов темы`);
console.log(`  адрес сайта: ${TARGET}/`);
