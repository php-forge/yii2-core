<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

use yii\rbac\Item;
use yii\rbac\Rule;

/**
 * Description of ActionRule.
 */
class ActionRule extends Rule
{
    public string $name = 'action_rule';
    public string $action = 'read';

    /**
     * Private and protected properties to ensure that serialized object does not get corrupted after saving into the DB
     * because of null-bytes in the string.
     *
     * @see https://github.com/yiisoft/yii2/issues/10176
     * @see https://github.com/yiisoft/yii2/issues/12681
     */
    private string $somePrivateProperty = '';
    protected string $someProtectedProperty = '';

    public function execute(string|int $user, Item $item, array $params): bool
    {
        return $this->action === 'all' || $this->action === $params['action'];
    }
}
