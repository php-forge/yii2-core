<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

use yii\rbac\Item;
use yii\rbac\Rule;

/**
 * Checks if authorID matches userID passed via params.
 */
class AuthorRule extends Rule
{
    public string $name = 'isAuthor';
    public bool $reallyReally = false;

    /**
     * {@inheritdoc}
     */
    public function execute(string|int $user, Item $item, array $params): bool
    {
        return $params['authorID'] == $user;
    }
}
