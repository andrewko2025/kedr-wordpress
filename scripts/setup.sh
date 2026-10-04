#!/bin/sh
# Разворачивает демо-сайт «Кедр»: установка WordPress, русский язык, тема и демо-контент.
# Запуск: docker compose run --rm cli sh /scripts/setup.sh
# Скрипт можно запускать повторно — существующие данные не дублируются.
set -eu

cd /var/www/html

echo "→ Жду, пока контейнер WordPress подготовит файлы…"
tries=0
until [ -f wp-config.php ]; do
	tries=$((tries + 1))
	if [ "$tries" -gt 60 ]; then
		echo "Не дождался wp-config.php. Проверьте: docker compose logs wordpress" >&2
		exit 1
	fi
	sleep 2
done

if ! wp core is-installed 2>/dev/null; then
	echo "→ Устанавливаю WordPress…"
	wp core install \
		--url="$WP_URL" \
		--title="Кедр" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email
fi

echo "→ Русский язык…"
wp language core install ru_RU --activate || echo "  Не удалось скачать перевод — админка останется на английском."

echo "→ Настройки…"
wp option update timezone_string "Europe/Moscow"
wp option update date_format "j F Y"
wp option update time_format "H:i"
wp option update start_of_week 1
wp option update blog_public 0
wp option update default_comment_status closed
wp option update default_ping_status closed
wp rewrite structure "/%postname%/"

echo "→ Активирую тему «Кедр»…"
wp theme activate kedr

echo "→ Демо-контент…"
wp eval-file /scripts/seed.php
wp rewrite flush

echo ""
echo "Готово! Сайт: $WP_URL"
echo "Админка: $WP_URL/wp-admin (логин и пароль — в файле .env)"
