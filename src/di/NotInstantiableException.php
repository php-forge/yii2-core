<?php

declare(strict_types=1);

namespace yii\di;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use yii\base\InvalidConfigException;

/**
 * NotInstantiableException represents an exception caused by incorrect dependency injection container configuration or
 * usage.
 */
class NotInstantiableException extends InvalidConfigException implements NotFoundExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        string $class,
        string|null $message = null,
        int $code = 0,
        Throwable|null $previous = null
    ) {
        if ($message === null) {
            $message = "Can not instantiate $class.";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception.
     */
    public function getName(): string
    {
        return 'Not instantiable';
    }
}
