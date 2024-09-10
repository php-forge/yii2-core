<?php

declare(strict_types=1);

namespace yii\db;

enum PHPType: string
{
    case INTEGER = 'integer';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case DOUBLE = 'double';
    case RESOURCE = 'resource';
    case ARRAY = 'array';
    case NULL = 'NULL';
}
