<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;
use yii\db\Query;

abstract class AbstractBatchInsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    /**
     * Make sure that `{{something}}` in values will not be encoded.
     *
     * {@see https://github.com/yiisoft/yii2/issues/11242}
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        $command = $this->db->createCommand();
        $command->batchInsert($table, $columns, $values);

        $this->assertSame($expected, $command->getSql());
        $this->assertSame($expectedParams, $command->params);

        $command->prepare(false);
        $command->execute();

        $this->assertEquals($insertedRow, (new Query())->from($table)->count(db: $this->db));
    }

    /**
     * Test batch insert with different data types.
     *
     * Ensure double is inserted with `.` decimal separator.
     *
     * https://github.com/yiisoft/yii2/issues/6526
     */
    public function testBatchInsertDataTypesLocale(): void
    {
        $locale = setlocale(LC_NUMERIC, 0);

        if (false === $locale) {
            $this->markTestSkipped('Your platform does not support locales.');
        }

        try {
            // This one sets decimal mark to comma sign
            setlocale(LC_NUMERIC, 'ru_RU.utf8');

            $cols = ['int_col', 'char_col', 'float_col', 'bool_col'];
            $data = [
                [1, 'A', 9.735, true],
                [2, 'B', -2.123, false],
                [3, 'C', 2.123, false],
            ];

            // clear data in "type" table
            $this->db->createCommand()->delete('type')->execute();

            // batch insert on "type" table
            $result = $this->db->createCommand()->batchInsert('type', $cols, $data)->execute();

            $this->assertSame(3, $result);

            $data = $this->db->createCommand(
                <<<SQL
                SELECT [[int_col]], [[char_col]], [[float_col]], [[bool_col]] FROM {{type}} WHERE [[int_col]] IN (1,2,3) ORDER BY [[int_col]]
                SQL
            )->queryAll();

            $this->assertEquals(3, \count($data));
            $this->assertEquals(1, $data[0]['int_col']);
            $this->assertEquals(2, $data[1]['int_col']);
            $this->assertEquals(3, $data[2]['int_col']);
            $this->assertEquals('A', rtrim($data[0]['char_col'])); // rtrim because Postgres padds the column with whitespace
            $this->assertEquals('B', rtrim($data[1]['char_col']));
            $this->assertEquals('C', rtrim($data[2]['char_col']));
            $this->assertEquals('9.735', $data[0]['float_col']);
            $this->assertEquals('-2.123', $data[1]['float_col']);
            $this->assertEquals('2.123', $data[2]['float_col']);
            $this->assertEquals('1', $data[0]['bool_col']);
            $this->assertIsOneOf($data[1]['bool_col'], ['0', false]);
            $this->assertIsOneOf($data[2]['bool_col'], ['0', false]);

        } catch (\Exception $e) {
            setlocale(LC_NUMERIC, $locale);
            throw $e;
        } catch (\Throwable $e) {
            setlocale(LC_NUMERIC, $locale);
            throw $e;
        }

        setlocale(LC_NUMERIC, $locale);
    }

    public function testBatchInsertWithCallableEmptyRow(): void
    {
        $command = $this->db->createCommand();

        $rows = call_user_func(
            static function () {
                if (false) {
                    yield [];
                }
            }
        );

        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            $rows
        );

        $this->assertSame(0, $command->execute());
    }

    public function testBatchInsertWithYieldEmptyRow(): void
    {
        $command = $this->db->createCommand();

        $rows = (static fn () => yield [])();

        $command->batchInsert(
            '{{customer}}',
            ['email', 'name', 'address'],
            $rows
        );

        $this->assertSame(0, $command->execute());
    }
}
