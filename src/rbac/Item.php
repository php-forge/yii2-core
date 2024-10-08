<?php

declare(strict_types=1);

namespace yii\rbac;

use yii\base\BaseObject;

/**
 * For more details and usage information on Item, see the [guide article on security authorization](guide:security-authorization).
 */
class Item extends BaseObject
{
    const TYPE_ROLE = 1;
    const TYPE_PERMISSION = 2;

    /**
     * @var int the type of the item. This should be either [[TYPE_ROLE]] or [[TYPE_PERMISSION]].
     */
    public int $type = self::TYPE_ROLE;
    /**
     * @var string the name of the item. This must be globally unique.
     */
    public string $name = '';
    /**
     * @var string|null the item description
     */
    public string|null $description = null;
    /**
     * @var string|null name of the rule associated with this item
     */
    public string|null $ruleName = null;
    /**
     * @var mixed the additional data associated with this item
     */
    public mixed $data = null;
    /**
     * @var int|null UNIX timestamp representing the item creation time
     */
    public int|null $createdAt = null;
    /**
     * @var int UNIX timestamp representing the item updating time
     */
    public int|null $updatedAt = null;
}
