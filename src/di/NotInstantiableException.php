<?php

declare(strict_types=1);

namespace yii\di;

use Throwable;
use yii\base\InvalidConfigException;

/**
 * NotInstantiableException represents an exception caused by incorrect dependency injection container configuration or
 * usage.
 */
class NotInstantiableException extends InvalidConfigException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string|null $message = null, int $code = 0, Throwable|null $previous = null)
    {
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
