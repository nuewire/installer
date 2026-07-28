<?php

declare(strict_types=1);

return [
    'locale' => env('NUEWIRE_INSTALLER_LOCALE', 'id'),
    'composer_binary' => env('NUEWIRE_COMPOSER_BINARY'),

    'ui' => [
        'enabled' => (bool) env('NUEWIRE_INSTALLER_UI', true),
        'allow_updates' => (bool) env('NUEWIRE_INSTALLER_UI_ALLOW_UPDATES', true),
        'allowed_environments' => ['local'],
        'process_timeout' => 600,
        'authorization' => [
            'require_authenticated_user' => true,
            'gate' => env('NUEWIRE_INSTALLER_UI_GATE'),
            'guard' => env('NUEWIRE_INSTALLER_UI_GUARD'),
        ],
    ],

    'manager' => [
        'package' => 'nuewire/installer',
        'label' => ['id' => 'Installer', 'en' => 'Installer'],
        'description' => ['id' => 'Instalasi dan pembaruan package.', 'en' => 'Package installation and updates.'],
        'order' => 0,
    ],

    'remote_catalog' => [
        'enabled' => (bool) env('NUEWIRE_CATALOG_ENABLED', false),
        'url' => env('NUEWIRE_CATALOG_URL'),
        'public_key' => env('NUEWIRE_CATALOG_PUBLIC_KEY'),
        'timeout' => (int) env('NUEWIRE_CATALOG_TIMEOUT', 5),
    ],

    'features' => [
        'platform' => [
            'package' => 'nuewire/platform',
            'constraint' => '^1.0',
            'label' => ['id' => 'Platform', 'en' => 'Platform'],
            'description' => ['id' => 'Layout dan navigasi admin.', 'en' => 'Admin layout and navigation.'],
            'recommended' => true,
            'order' => 10,
        ],
        'users' => [
            'package' => 'nuewire/users',
            'constraint' => '^1.0',
            'label' => ['id' => 'Pengguna', 'en' => 'Users'],
            'description' => ['id' => 'Pengguna dan aktivitas login.', 'en' => 'Users and login activity.'],
            'recommended' => true,
            'requires_features' => ['platform'],
            'setup_command' => 'nuewire:users:install',
            'order' => 15,
        ],
        'acl' => [
            'package' => 'nuewire/acl',
            'constraint' => '^1.0',
            'label' => ['id' => 'ACL', 'en' => 'ACL'],
            'description' => ['id' => 'Role dan permission.', 'en' => 'Roles and permissions.'],
            'recommended' => false,
            'requires_features' => ['users'],
            'setup_command' => 'nuewire:acl:install',
            'order' => 18,
        ],
        'filesystem' => [
            'package' => 'nuewire/filesystem',
            'constraint' => '^1.0',
            'label' => ['id' => 'Filesystem', 'en' => 'Filesystem'],
            'description' => ['id' => 'Local, S3, dan Bunny CDN.', 'en' => 'Local, S3, and Bunny CDN.'],
            'recommended' => true,
            'order' => 20,
        ],
        'mail' => [
            'package' => 'nuewire/mail',
            'constraint' => '^1.0',
            'label' => ['id' => 'Email', 'en' => 'Mail'],
            'description' => ['id' => 'SMTP, Resend, SES, dan lainnya.', 'en' => 'SMTP, Resend, SES, and more.'],
            'recommended' => true,
            'order' => 30,
        ],
    ],
];
