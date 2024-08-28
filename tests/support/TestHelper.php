<?php

declare(strict_types=1);

namespace yiiunit\support;

class TestHelper
{
    /**
     * Adds expected values to the data provider.
     *
     * @param array $expected expected values.
     * @param array $dataProvider The data provider to add the expected values to.
     *
     * @return array The data provider with the expected values added.
     */
    public static function addExpected(array $expected, array $dataProvider): array
    {
        $result = [];

        foreach ($expected as $testName => $data) {
            if (isset($dataProvider[$testName])) {
                $result[$testName] = array_replace($dataProvider[$testName], $data);
            }
        }

        return $result;
    }
}
