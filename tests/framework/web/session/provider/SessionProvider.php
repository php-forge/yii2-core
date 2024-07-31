<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\provider;

final class SessionProvider
{
    public static function setCacheLimiterDataProvider(): array
    {
        return [
            ['no-cache'],
            ['public'],
            ['private'],
            ['private_no_expire'],
        ];
    }

    public static function setCookieParamsDataProvider(): array
    {
        return [
            [
                [
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => false,
                    'samesite' => '',
                ]
            ],
            [
                [
                    'lifetime' => 3600,
                    'path' => '/path',
                    'domain' => 'example.com',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]
            ],
        ];
    }
}
