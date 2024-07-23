<?php

declare(strict_types=1);

namespace yii\rbac;

/**
 * For more details and usage information on Role, see the [guide article on security authorization](guide:security-authorization).
 */
class Role extends Item
{
    /**
     * {@inheritdoc}
     */
    public int $type = self::TYPE_ROLE;
}
