<?php

declare(strict_types=1);

namespace yiiunit\framework\db\oci\provider;

use yiiunit\framework\db\provider\AbstractQuoterProvider;

final class QuoterProvider extends AbstractQuoterProvider
{
    /**
     * @return string[][]
     */
    public static function columnNames(): array
    {
        return [
            ['*', '*'],
            ['table.*', '"table".*'],
            ['"table".*', '"table".*'],
            ['table.column', '"table"."column"'],
            ['"table".column', '"table"."column"'],
            ['table."column"', '"table"."column"'],
            ['"table"."column"', '"table"."column"'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function columnNameWithStartingEndingCharacter(): array
    {
        return [
            ['column', '"column"'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleColumnNames(): array
    {
        return [
            ['test', '"test"', 'test'],
            ['"test"', '"test"', 'test'],
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleTableNames(): array
    {
        return [
            ['test', 'test', ],
            ['te`st', 'te`st', ],
            ['te\'st', 'te\'st', ],
            ['current-table-name', 'current-table-name', ],
            ['"current-table-name"', 'current-table-name', ],
        ];
    }

   /**
     * @return string[][]
     */
    public static function stringValues(): array
    {
        return [
            ['string', "'string'"],
            ["It's interesting", "'It''s interesting'"],
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
            ['table', '"table"'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function tableNameWithSchema(): array
    {
        return [
            ['schema.table', '"schema"."table"'],
        ];
    }
}
