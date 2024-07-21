<?php

declare(strict_types=1);

namespace yiiunit\framework\rbac;

use yii\rbac\PhpManager;

/**
 * Exposes protected properties and methods to inspect from outside.
 */
class ExposedPhpManager extends PhpManager
{
    /**
     * @var \yii\rbac\Item[]
     */
    public array $items = []; // itemName => item
    /**
     * @var array
     */
    public array $children = []; // itemName, childName => child
    /**
     * @var \yii\rbac\Assignment[]
     */
    public array $assignments = []; // userId, itemName => assignment
    /**
     * @var \yii\rbac\Rule[]
     */
    public array $rules = []; // ruleName => rule

    public function load(): void
    {
        parent::load();
    }

    public function save(): void
    {
        parent::save();
    }
}
