<?php

declare(strict_types=1);

namespace yiiunit\framework\db\provider;

abstract class AbstractQuoterProvider
{
    /**
     * @return string[][]
     */
    public static function columnNames(): array
    {
        return [
            ['*', '*'],
            ['(parentheses)', '(parentheses)'],
            ['[[double_brackets]]', '[[double_brackets]]'],
            ['{{curly_brackets}}', '{{curly_brackets}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function columnNameWithStartingEndingCharacter(): array
    {
        return [
            ['column', '`column`'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function ensureColumnName(): array
    {
        return [
            ['*', '*'],
            ['`*`', '`*`'],
            ['[[*]]', '[[*]]'],
            ['{{*}}', '{{*}}'],
            ['table.column', 'column'],
            ['`table`.`column`', '`column`'],
            ['[[table]].[[column]]', 'column'],
            ['{{table}}.{{column}}', '{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleColumnNames(): array
    {
        return [
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleTableNames(): array
    {
        return [
            ['*', '*'],
            ['(parentheses)', '(parentheses)'],
            ['[[double_brackets]]', '[[double_brackets]]'],
            ['{{curly_brackets}}', '{{curly_brackets}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function stringValues(): array
    {
        return [
            ['string', "'string'"],
            ["It's interesting", "'It\\'s interesting'"],
        ];
    }

    /**
     * @return string[][]
     */
    public static function tableNameParts(): array
    {
        return [
            ['', ''],
            ['[]', '[]'],
            ['animal', 'animal'],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', '[animal]', '[dbo]'],
            ['[other].[animal2]', '[animal2]', '[other]'],
            ['other.[animal2]', '[animal2]', 'other'],
            ['other.animal2', 'animal2', 'other'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function tableNameWithStartingEndingCharacter(): array
    {
        return [
            ['table', '`table`'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function tableNameWithSchema(): array
    {
        return [
            ['schema.table', '`schema`.`table`'],
        ];
    }
}
