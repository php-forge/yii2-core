<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

final class SqlHelperProvider
{
    public static function readQuery(): array
    {
        return [
            'SELECT query' => ['SELECT * FROM users', true],
            'SELECT with whitespace' => ['   SELECT id FROM products', true],
            'SHOW query' => ['SHOW TABLES', true],
            'DESCRIBE query' => ['DESCRIBE users', true],
            'INSERT query' => ['INSERT INTO users (name) VALUES ("John")', false],
            'UPDATE query' => ['UPDATE users SET name = "John" WHERE id = 1', false],
            'DELETE query' => ['DELETE FROM users WHERE id = 1', false],
        ];
    }
}
