<?php
/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use yii\db\DefaultValueConstraint;
use yii\db\mssql\Schema;
use yiiunit\framework\db\AnyValue;

/**
 * @group db
 * @group mssql
 */
class SchemaTest extends \yiiunit\framework\db\SchemaTest
{
    public $driverName = 'sqlsrv';

    protected $expectedSchemas = [
        'dbo',
    ];

    public function constraintsProvider()
    {
        $result = parent::constraintsProvider();
        $result['1: check'][2][0]->expression = '([C_check]<>\'\')';
        $result['1: default'][2] = [];
        $result['1: default'][2][] = new DefaultValueConstraint([
            'name' => AnyValue::getInstance(),
            'columnNames' => ['C_default'],
            'value' => '((0))',
        ]);

        $result['2: default'][2] = [];

        $result['3: foreign key'][2][0]->foreignSchemaName = 'dbo';
        $result['3: index'][2] = [];
        $result['3: default'][2] = [];

        $result['4: default'][2] = [];
        return $result;
    }

    public function testGetStringFieldsSize()
    {
        /* @var $db Connection */
        $db = $this->getConnection();

        /* @var $schema Schema */
        $schema = $db->schema;

        $columns = $schema->getTableSchema('type', false)->columns;

        foreach ($columns as $name => $column) {
            $type = $column->type;
            $size = $column->size;
            $dbType = $column->dbType;

            if (strpos($name, 'char_') === 0) {
                switch ($name) {
                    case 'char_col':
                        $expectedType = 'char';
                        $expectedSize = 100;
                        $expectedDbType = 'char(100)';
                        break;
                    case 'char_col2':
                        $expectedType = 'string';
                        $expectedSize = 100;
                        $expectedDbType = "varchar(100)";
                        break;
                    case 'char_col3':
                        $expectedType = 'text';
                        $expectedSize = null;
                        $expectedDbType = 'text';
                        break;
                }

                $this->assertEquals($expectedType, $type);
                $this->assertEquals($expectedSize, $size);
                $this->assertEquals($expectedDbType, $dbType);
            }
        }
    }

    /**
     * @dataProvider getTableSchemaDataProvider
     * @param $name
     * @param $expectedName
     * @throws \yii\base\NotSupportedException
     */
    public function testGetTableSchema($name, $expectedName)
    {
        $schema = $this->getConnection()->getSchema();
        $tableSchema = $schema->getTableSchema($name);
        $this->assertEquals($expectedName, $tableSchema->name);
    }

    public function getTableSchemaDataProvider()
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
        ];
    }

    public function getExpectedColumns()
    {
        $columns = parent::getExpectedColumns();

        unset($columns['enum_col']);
        unset($columns['ts_default']);
        unset($columns['bit_col']);
        unset($columns['json_col']);

        $columns['int_col']['dbType'] = 'int';
        $columns['int_col2']['dbType'] = 'int';
        $columns['tinyint_col']['dbType'] = 'tinyint';
        $columns['smallint_col']['dbType'] = 'smallint';
        $columns['float_col']['dbType'] = 'decimal(4,3)';
        $columns['float_col']['type'] = 'decimal';
        $columns['float_col2']['dbType'] = 'float';
        $columns['float_col2']['phpType'] = 'double';
        $columns['float_col2']['type'] = 'float';
        $columns['blob_col']['dbType'] = 'varbinary';
        $columns['numeric_col']['dbType'] = 'decimal(5,2)';
        $columns['numeric_col']['scale'] = 2;
        $columns['time']['dbType'] = 'datetime';
        $columns['time']['type'] = 'datetime';
        $columns['bool_col']['dbType'] = 'tinyint';
        $columns['bool_col2']['dbType'] = 'tinyint';

        array_walk($columns, static function (&$item) {
            $item['enumValues'] = [];
        });

        array_walk($columns, static function (&$item, $name) {
            if (!in_array($name, ['char_col', 'char_col2', 'char_col3', 'float_col', 'numeric_col'])) {
                $item['size'] = null;
            }
        });

        array_walk($columns, static function (&$item, $name) {
            if (!in_array($name, ['char_col', 'char_col2', 'char_col3', 'float_col', 'numeric_col'])) {
                $item['precision'] = null;
            }
        });

        return $columns;
    }
}
