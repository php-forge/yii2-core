<?php

declare(strict_types=1);

namespace yii\di;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends NotInstantiableException implements NotFoundExceptionInterface
{
}
