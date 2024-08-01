<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\provider;

final class I18NProvider
{
    public static function sourceLanguageDataProvider(): array
    {
        return [
            ['en-GB'],
            ['en'],
        ];
    }
}
