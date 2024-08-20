<?php

declare(strict_types=1);

namespace yiiunit\framework\db\mssql\querybuilder;

use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yiiunit\support\fixture\Customer;
use yiiunit\support\MssqlConnection;
use yiiunit\support\TableGenerator;

/**
 * @group db
 * @group mssql
 * @group querybuilder
 * @group comment
 */
final class CommentOnTest extends \yiiunit\TestCase
{
    private Connection|null $db = null;

    public function setup(): void
    {
        parent::setUp();

        $this->db = MssqlConnection::getConnection();
    }

    public function testAddCommentOnColumn(): void
    {
        TableGenerator::ensureTable($this->db, Customer::class);

        $qb = $this->db->getQueryBuilder();

        $expectedSQL = <<<SQL
                IF NOT EXISTS (    SELECT 1
                FROM fn_listextendedproperty (
                    N'MS_description',
                    'SCHEMA', N'dbo',
                    'TABLE', N'customer',
                    " . (id ? "'COLUMN', N'id' " : ' DEFAULT, DEFAULT ') . "
                ))
                    EXEC sys.sp_addextendedproperty 
                        @name = N'MS_description',
                        @value = N'Primary key.',
                        @level0type = N'SCHEMA', @level0name = N'dbo',
                        @level1type = N'TABLE', @level1name = N'customer', @level2type = N'COLUMN', @level2name = N'id';
                ELSE
                    EXEC sys.sp_updateextendedproperty 
                        @name = N'MS_description',
                        @value = N'Primary key.',
                        @level0type = N'SCHEMA', @level0name = N'dbo',
                        @level1type = N'TABLE', @level1name = N'customer', @level2type = N'COLUMN', @level2name = N'id';
            SQL;

        $this->assertEquals($expectedSQL, $qb->addCommentOnColumn('customer', 'id', 'Primary key.'));

        TableGenerator::ensureNoTable($this->db, 'customer');
    }

    public function testAddCommentOnColumnWithException(): void
    {
        $qb = $this->db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnColumn('noExist', 'id', 'Primary key.');
    }

    public function testAddCommentOnTable(): void
    {
        TableGenerator::ensureTable($this->db, Customer::class);

        $qb = $this->db->getQueryBuilder();

        $expectedSQL = <<<SQL
                IF NOT EXISTS (    SELECT 1
                FROM fn_listextendedproperty (
                    N'MS_description',
                    'SCHEMA', N'dbo',
                    'TABLE', N'customer',
                    " . ( ? "'COLUMN',  " : ' DEFAULT, DEFAULT ') . "
                ))
                    EXEC sys.sp_addextendedproperty 
                        @name = N'MS_description',
                        @value = N'Customer table.',
                        @level0type = N'SCHEMA', @level0name = N'dbo',
                        @level1type = N'TABLE', @level1name = N'customer';
                ELSE
                    EXEC sys.sp_updateextendedproperty 
                        @name = N'MS_description',
                        @value = N'Customer table.',
                        @level0type = N'SCHEMA', @level0name = N'dbo',
                        @level1type = N'TABLE', @level1name = N'customer';
            SQL;

        $this->assertSame($expectedSQL, $qb->addCommentOnTable('customer', 'Customer table.'));

        TableGenerator::ensureNoTable($this->db, 'customer');
    }

    public function testAddCommentOnTableWithException(): void
    {
        $qb = $this->db->getQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table not found: noExist');

        $qb->addCommentOnTable('noExist', 'Customer table.');
    }
}
