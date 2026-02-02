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

# Создаём yii (console entry point), если его нет
if [ ! -f "/app/yii" ]; then
  echo "🧩 Создаю console entry point (yii) в стиле Yii2 Advanced..."
  cat > /app/yii <<'PHP'
#!/usr/bin/env php
<?php
/**
 * Yii console bootstrap file (docker-friendly).
 */

defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') !== false ? (bool)getenv('YII_DEBUG') : true);
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

$commonBootstrap = __DIR__ . '/common/config/bootstrap.php';
$consoleBootstrap = __DIR__ . '/console/config/bootstrap.php';

if (is_file($commonBootstrap)) {
    require $commonBootstrap;
}
if (is_file($consoleBootstrap)) {
    require $consoleBootstrap;
}

$files = [
    __DIR__ . '/common/config/main.php',
    __DIR__ . '/console/config/main.php',
];

// local-файлы подключаем только если они существуют
$commonLocal = __DIR__ . '/common/config/main-local.php';
$consoleLocal = __DIR__ . '/console/config/main-local.php';

if (is_file($commonLocal)) {
    $files[] = $commonLocal;
}
if (is_file($consoleLocal)) {
    $files[] = $consoleLocal;
}

$config = [];
foreach ($files as $file) {
    $config = yii\helpers\ArrayHelper::merge($config, require $file);
}

$application = new yii\console\Application($config);
exit($application->run());
PHP

  chmod +x /app/yii
fi

# 4) Миграции
echo "🛠️ Миграции..."
php yii migrate --interactive=0 || true

echo "🚀 Запуск Apache..."
exec apache2-foreground