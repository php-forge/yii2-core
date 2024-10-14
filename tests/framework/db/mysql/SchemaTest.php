<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;
use yii\db\Expression;

use yii\db\mysql\ColumnSchema;
use yii\db\mysql\Schema;
use yiiunit\framework\db\AnyCaseValue;

/**
 * @group db
 * @group mysql
 */
class SchemaTest extends \yiiunit\framework\db\SchemaTest
{
    public $driverName = 'mysql';

    public function testLoadDefaultDatetimeColumn(): void
    {

        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `datetime_test`  (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $this->getConnection()->createCommand($sql)->execute();

        $schema = $this->getConnection()->getTableSchema('datetime_test');

        $dt = $schema->columns['dt'];

        $this->assertInstanceOf(Expression::className(), $dt->defaultValue);
        $this->assertEquals('CURRENT_TIMESTAMP', (string)$dt->defaultValue);
    }

    public function testDefaultDatetimeColumnWithMicrosecs(): void
    {
        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `current_timestamp_test`  (
        `dt` datetime(2) NOT NULL DEFAULT CURRENT_TIMESTAMP(2),
        `ts` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $this->getConnection()->createCommand($sql)->execute();

        $schema = $this->getConnection()->getTableSchema('current_timestamp_test');

        $dt = $schema->columns['dt'];
        $this->assertInstanceOf(Expression::className(), $dt->defaultValue);
        $this->assertEquals('CURRENT_TIMESTAMP(2)', (string)$dt->defaultValue);

        $ts = $schema->columns['ts'];
        $this->assertInstanceOf(Expression::className(), $ts->defaultValue);
        $this->assertEquals('CURRENT_TIMESTAMP(3)', (string)$ts->defaultValue);
    }

    public function testGetSchemaNames()
    {
        $this->markTestSkipped('Schemas are not supported in MySQL.');
    }

    public function constraintsProvider()
    {
        $result = parent::constraintsProvider();

        $result['1: check'][2][0]->columnNames = null;
        $result['1: check'][2][0]->expression = "(`C_check` <> _utf8mb4\\'\\')";
        $result['2: primary key'][2]->name = null;

        // Work aroung bug in MySQL 5.1 - it creates only this table in lowercase. O_o
        $result['3: foreign key'][2][0]->foreignTableName = new AnyCaseValue('T_constraints_2');

        return $result;
    }

    /**
     * @dataProvider constraintsProvider
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraints($tableName, $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException('yii\base\NotSupportedException');
        }

        $constraints = $this->getConnection(false)->getSchema()->{'getTable' . ucfirst($type)}($tableName);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProvider
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException('yii\base\NotSupportedException');
        }

        $connection = $this->getConnection(false);
        $connection->getSlavePdo(true)->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);
        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProvider
     * @param string $tableName
     * @param string $type
     * @param mixed $expected
     */
    public function testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected): void
    {
        if ($expected === false) {
            $this->expectException('yii\base\NotSupportedException');
        }

        $connection = $this->getConnection(false);
        $connection->getSlavePdo(true)->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
     * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
     *
     * @see https://mariadb.com/kb/en/library/now/#description
     * @see https://github.com/yiisoft/yii2/issues/15167
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampInMariaDB()
    {
        /**
         * We do not have a real database MariaDB >= 10.2.3 for tests, so we emulate the information that database
         * returns in response to the query `SHOW FULL COLUMNS FROM ...`
         */
        $schema = new Schema();
        $column = $this->invokeMethod($schema, 'loadColumnSchema', [[
            'field' => 'emulated_MariaDB_field',
            'type' => 'timestamp',
            'collation' => NULL,
            'null' => 'NO',
            'key' => '',
            'default' => 'current_timestamp()',
            'extra' => '',
            'privileges' => 'select,insert,update,references',
            'comment' => '',
        ]]);

        $this->assertInstanceOf(ColumnSchema::className(), $column);
        $this->assertInstanceOf(Expression::className(), $column->defaultValue);
        $this->assertEquals('CURRENT_TIMESTAMP', $column->defaultValue);
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is provided
     * as NULL.
     *
     * @see https://github.com/yiisoft/yii2/issues/19047
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampAsNullInMariaDB()
    {
        $schema = new Schema();
        $column = $this->invokeMethod($schema, 'loadColumnSchema', [[
            'field' => 'emulated_MariaDB_field',
            'type' => 'timestamp',
            'collation' => NULL,
            'null' => 'NO',
            'key' => '',
            'default' => NULL,
            'extra' => '',
            'privileges' => 'select,insert,update,references',
            'comment' => '',
        ]]);

        $this->assertInstanceOf(ColumnSchema::className(), $column);
        $this->assertEquals(NULL, $column->defaultValue);
    }

    public function getExpectedColumns()
    {
        $version = $this->getConnection(false)->getServerVersion();

        $columns = array_merge(
            parent::getExpectedColumns(),
            [
                'int_col' => [
                    'type' => 'integer',
                    'dbType' => 'int(11)',
                    'phpType' => 'integer',
                    'allowNull' => false,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 11,
                    'precision' => 11,
                    'scale' => null,
                    'defaultValue' => null,
                ],
                'int_col2' => [
                    'type' => 'integer',
                    'dbType' => 'int(11)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 11,
                    'precision' => 11,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'int_col3' => [
                    'type' => 'integer',
                    'dbType' => 'int(11) unsigned',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 11,
                    'precision' => 11,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'tinyint_col' => [
                    'type' => 'tinyint',
                    'dbType' => 'tinyint(3)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 3,
                    'precision' => 3,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'smallint_col' => [
                    'type' => 'smallint',
                    'dbType' =>  'smallint(1)',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 1,
                    'precision' => 1,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'bigint_col' => [
                    'type' => 'bigint',
                    'dbType' => 'bigint(20) unsigned',
                    'phpType' => 'string',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => [],
                    'size' => 20,
                    'precision' => 20,
                    'scale' => null,
                    'defaultValue' => null,
                ],
            ]
        );

        $columns['int_col']['dbType'] = 'int';
        $columns['int_col']['size'] = null;
        $columns['int_col']['precision'] = null;
        $columns['int_col2']['dbType'] = 'int';
        $columns['int_col2']['size'] = null;
        $columns['int_col2']['precision'] = null;
        $columns['int_col3']['dbType'] = 'int unsigned';
        $columns['int_col3']['size'] = null;
        $columns['int_col3']['precision'] = null;
        $columns['tinyint_col']['dbType'] = 'tinyint';
        $columns['tinyint_col']['size'] = null;
        $columns['tinyint_col']['precision'] = null;
        $columns['smallint_col']['dbType'] = 'smallint';
        $columns['smallint_col']['size'] = null;
        $columns['smallint_col']['precision'] = null;
        $columns['bigint_col']['dbType'] = 'bigint unsigned';
        $columns['bigint_col']['size'] = null;
        $columns['bigint_col']['precision'] = null;

        return $columns;
    }
}
