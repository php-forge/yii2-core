<?php

declare(strict_types=1);

namespace yii\di;

use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no entry was found in the container.
 */
final class NotFoundException extends NotInstantiableException implements NotFoundExceptionInterface
{
}
