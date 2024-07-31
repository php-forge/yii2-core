<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use yii\web\session\DbSession;

class DbSessionStub extends DbSession
{
    public static int|null $counter = 0;
}
