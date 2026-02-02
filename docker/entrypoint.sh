#!/bin/bash
set -e

cd /app

echo "⏳ Жду доступности MySQL ($DB_HOST)..."
until mysqladmin ping -h"$DB_HOST" --silent; do
  sleep 3
done
echo "✅ MySQL доступен."

# 2) Установка зависимостей (если vendor не существует)
if [ ! -d "/app/vendor" ]; then
  echo "📦 Composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  echo "📦 vendor уже есть, composer install пропускаю."
fi

# 3) Права/папки runtime/assets (frontend+backend+console)
echo "🔧 Создаю runtime/assets..."
mkdir -p \
  /app/frontend/runtime /app/frontend/web/assets \
  /app/backend/runtime /app/backend/web/assets \
  /app/console/runtime

echo "🔧 Права доступа..."
chown -R www-data:www-data \
  /app/frontend/runtime /app/frontend/web/assets \
  /app/backend/runtime /app/backend/web/assets \
  /app/console/runtime || true

chmod -R 775 \
  /app/frontend/runtime /app/frontend/web/assets \
  /app/backend/runtime /app/backend/web/assets \
  /app/console/runtime || true

# 4) Миграции
echo "🛠️ Миграции..."
php yii migrate --interactive=0 || true

echo "🚀 Запуск Apache..."
exec apache2-foreground