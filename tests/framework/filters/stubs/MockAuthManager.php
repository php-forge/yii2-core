<?php

declare(strict_types=1);

namespace yiiunit\framework\filters\stubs;

use yii\rbac\PhpManager;

class MockAuthManager extends PhpManager
{
    /**
     * This mock does not persist.
     * {@inheritdoc}
     */
    protected function saveToFile(array $data, string $file): void
    {
    }
}
