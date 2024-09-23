<?php

declare(strict_types=1);

namespace yii\db\mysql;

use function dechex;
use function is_string;
use function ord;
use function preg_replace_callback;
use function str_replace;

/**
 * Implements the MySQL Server quoting and unquoting methods.
 */
final class Quoter extends \yii\db\Quoter
{
    /**
     * {@inheritdoc}
     */
    public function quoteValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $escaped = str_replace(
            ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $value
        );

        $escaped = preg_replace_callback(
            '/[\x00-\x1F\x7F-\xFF]/',
            static function ($matches): string {
                return '\\' . dechex(ord($matches[0]));
            },
            $escaped,
        );

        return "'" . $escaped . "'";
    }
}
