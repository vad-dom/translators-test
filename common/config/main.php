<?php

use yii\caching\FileCache;
use yii\db\Connection;

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => FileCache::class,
        ],
        'db' => [
            'class' => Connection::class,
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'db')
                . ';dbname=' . (getenv('DB_NAME') ?: 'translators_test'),
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: 'root',
            'charset' => 'utf8mb4',
        ],
    ],
];
