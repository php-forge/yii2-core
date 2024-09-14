<?php

declare(strict_types=1);

namespace yiiunit\framework\db\command;

use yii\db\Connection;
use yii\db\Query;
use yii\db\QueryInterface;

use function json_encode;

abstract class AbstractUpsert extends \yiiunit\TestCase
{
    protected Connection|null $db = null;

    public function tearDown(): void
    {
        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testExecuteUpsert(
        string $tableName,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        $actualParams = [];

        $qb = $this->db->getQueryBuilder();
        $actualSQL = $qb->upsert($tableName, $insertColumns, $updateColumns, $actualParams);

        $countQuery = (new Query())->from($tableName)->select('count(*)');
        $rowCountBefore = (int) $countQuery->createCommand($this->db)->queryScalar();
        $command = $this->db->createCommand($actualSQL, $actualParams);

        $this->assertEquals(1, $command->execute());

        $rowCountAfter = (int) $countQuery->createCommand($this->db)->queryScalar();

        $this->assertEquals(1, $rowCountAfter - $rowCountBefore);

        $command = $this->db->createCommand($actualSQL, $actualParams);

        $command->execute();
    }

    public function testExecuteUpsertWithColumnVarbinary(): void
    {
        $params = [];
        $testData = json_encode(['test' => 'string', 'test2' => 'integer'], JSON_THROW_ON_ERROR);

        $result = $this->db
            ->createCommand()
            ->upsert(
                'T_upsert_varbinary',
                ['id' => 1, 'blob_col' => $testData],
                ['blob_col' => $testData],
                $params
            )
            ->execute();

        $this->assertSame(1, $result);

        $query = (new Query())->select(['blob_col as blob_col'])->from('T_upsert_varbinary')->where(['id' => 1]);
        $resultData = $query->createCommand($this->db)->queryOne();

        $this->assertIsArray($resultData);

        if (is_resource($resultData['blob_col'])) {
            $resultData['blob_col'] = stream_get_contents($resultData['blob_col']);
        }

        $this->assertSame($testData, $resultData['blob_col']);
    }
}
