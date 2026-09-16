<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        foreach ([
            'APP_ENV' => 'testing',
            'BCRYPT_ROUNDS' => '4',
            'CACHE_STORE' => 'array',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'SESSION_ENCRYPT' => 'false',
            'SESSION_SECURE_COOKIE' => 'false',
        ] as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }

        $connection = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: '';
        if (in_array($connection, ['pgsql', 'mysql'], true)) {
            $_ENV['DB_DATABASE'] = 'pl_tournament_test';
            $_SERVER['DB_DATABASE'] = 'pl_tournament_test';
            putenv('DB_DATABASE=pl_tournament_test');
        }

        parent::setUp();

        $this->withoutVite();
    }
}
