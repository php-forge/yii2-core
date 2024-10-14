<?php

namespace yiiunit\framework\db\mysql;

use yiiunit\data\ar\Storage;

/**
 * @group db
 * @group mysql
 */
class BaseActiveRecordTest extends \yiiunit\framework\db\BaseActiveRecordTest
{
    public $driverName = 'mysql';

    /**
     * @see https://github.com/yiisoft/yii2/issues/19872
     *
     * @dataProvider provideArrayValueWithChange
     */
    public function testJsonDirtyAttributesWithDataChange($actual, $modified): void
    {
        $createdStorage = new Storage(['data' => $actual]);

        $createdStorage->save();

        $foundStorage = Storage::find()->limit(1)->one();

        $this->assertNotNull($foundStorage);

        $foundStorage->data = $modified;

        $this->assertSame(['data' => $modified], $foundStorage->getDirtyAttributes());
    }
}
