<?php

declare(strict_types=1);

return [
    'locale' => env('BTEKNO_INSTALLER_LOCALE', 'id'),
    'composer_binary' => env('BTEKNO_COMPOSER_BINARY'),

    'manager' => [
        'package' => 'btekno/installer',
        'label' => ['id' => 'Installer', 'en' => 'Installer'],
        'description' => ['id' => 'Instalasi dan pembaruan package.', 'en' => 'Package installation and updates.'],
        'order' => 0,
    ],

    'remote_catalog' => [
        'enabled' => (bool) env('BTEKNO_CATALOG_ENABLED', false),
        'url' => env('BTEKNO_CATALOG_URL'),
        'public_key' => env('BTEKNO_CATALOG_PUBLIC_KEY'),
        'timeout' => (int) env('BTEKNO_CATALOG_TIMEOUT', 5),
    ],

    'features' => [
        'platform' => [
            'package' => 'btekno/platform',
            'constraint' => '^1.0',
            'label' => ['id' => 'Platform', 'en' => 'Platform'],
            'description' => ['id' => 'Layout dan navigasi admin.', 'en' => 'Admin layout and navigation.'],
            'recommended' => true,
            'order' => 10,
        ],
        'filesystem' => [
            'package' => 'btekno/filesystem',
            'constraint' => '^1.0',
            'label' => ['id' => 'Filesystem', 'en' => 'Filesystem'],
            'description' => ['id' => 'Local, S3, dan Bunny CDN.', 'en' => 'Local, S3, and Bunny CDN.'],
            'recommended' => true,
            'order' => 20,
        ],
        'mail' => [
            'package' => 'btekno/mail',
            'constraint' => '^1.0',
            'label' => ['id' => 'Email', 'en' => 'Mail'],
            'description' => ['id' => 'SMTP, Resend, SES, dan lainnya.', 'en' => 'SMTP, Resend, SES, and more.'],
            'recommended' => true,
            'order' => 30,
        ],
    ],
];
