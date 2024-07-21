<?php

declare(strict_types=1);

namespace yii\rbac;

use Closure;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;

/**
 * BaseManager is a base class implementing [[ManagerInterface]] for RBAC management.
 *
 * For more details and usage information on DbManager, see the [guide article on security authorization](guide:security-authorization).
 *
 * @property-read Role[] $defaultRoleInstances Default roles. The array is indexed by the role names.
 * @property string[] $defaultRoles Default roles. Note that the type of this property differs in getter and
 * setter. See [[getDefaultRoles()]] and [[setDefaultRoles()]] for details.
 */
abstract class BaseManager extends Component implements ManagerInterface
{
    /**
     * @var array a list of role names that are assigned to every user automatically without calling [[assign()]].
     * Note that these roles are applied to users, regardless of their state of authentication.
     */
    protected array $defaultRoles = [];

    /**
     * Returns the named auth item.
     *
     * @param string $name the auth item name.
     *
     * @return Item|null the auth item corresponding to the specified name. Null is returned if no such item.
     */
    abstract protected function getItem(string $name): Item|null;

    /**
     * Returns the items of the specified type.
     *
     * @param int $type the auth item type (either [[Item::TYPE_ROLE]] or [[Item::TYPE_PERMISSION]].
     *
     * @return Item[] the auth items of the specified type.
     */
    abstract protected function getItems(int $type): array;

    /**
     * Adds an auth item to the RBAC system.
     *
     * @param Item $item the item to add.
     *
     * @return bool whether the auth item is successfully added to the system.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique).
     */
    abstract protected function addItem(Item $item): bool;

    /**
     * Adds a rule to the RBAC system.
     *
     * @param Rule $rule the rule to add.
     *
     * @return bool whether the rule is successfully added to the system.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique).
     */
    abstract protected function addRule(Rule $rule): bool;

    /**
     * Removes an auth item from the RBAC system.
     *
     * @param Item $item the item to remove.
     *
     * @return bool whether the role or permission is successfully removed.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique).
     */
    abstract protected function removeItem(Item $item): bool;

    /**
     * Removes a rule from the RBAC system.
     *
     * @param Rule $rule the rule to remove.
     *
     * @return bool whether the rule is successfully removed.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique).
     */
    abstract protected function removeRule(Rule $rule): bool;

    /**
     * Updates an auth item in the RBAC system.
     *
     * @param string $name the name of the item being updated.
     * @param Item $item the updated item.
     *
     * @return bool whether the auth item is successfully updated.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique).
     */
    abstract protected function updateItem(string $name, Item $item): bool;

    /**
     * Updates a rule to the RBAC system.
     *
     * @param string $name the name of the rule being updated.
     * @param Rule $rule the updated rule.
     *
     * @return bool whether the rule is successfully updated.
     *
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique).
     */
    abstract protected function updateRule(string $name, Rule $rule): bool;

    /**
     * {@inheritdoc}
     */
    public function createRole(string $name): Role
    {
        $role = new Role();
        $role->name = $name;

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function createPermission(string $name): Permission
    {
        $permission = new Permission();
        $permission->name = $name;

        return $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function add(Role|Permission|Rule $object): bool
    {
        if ($object instanceof Item) {
            if ($object->ruleName && $this->getRule($object->ruleName) === null) {
                $rule = \Yii::createObject($object->ruleName);
                $rule->name = $object->ruleName;
                $this->addRule($rule);
            }

            return $this->addItem($object);
        } elseif ($object instanceof Rule) {
            return $this->addRule($object);
        }

        throw new InvalidArgumentException('Adding unsupported object type.');
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Role|Permission|Rule $object): bool
    {
        if ($object instanceof Item) {
            return $this->removeItem($object);
        } elseif ($object instanceof Rule) {
            return $this->removeRule($object);
        }

        throw new InvalidArgumentException('Removing unsupported object type.');
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $name, Role|Permission|Rule $object): bool
    {
        if ($object instanceof Item) {
            if ($object->ruleName && $this->getRule($object->ruleName) === null) {
                $rule = \Yii::createObject($object->ruleName);
                $rule->name = $object->ruleName;
                $this->addRule($rule);
            }

            return $this->updateItem($name, $object);
        } elseif ($object instanceof Rule) {
            return $this->updateRule($name, $object);
        }

        throw new InvalidArgumentException('Updating unsupported object type.');
    }

    /**
     * {@inheritdoc}
     */
    public function getRole(string $name): Role|null
    {
        $item = $this->getItem($name);
        return $item instanceof Item && $item->type == Item::TYPE_ROLE ? $item : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermission(string $name): Permission|null
    {
        $item = $this->getItem($name);
        return $item instanceof Item && $item->type == Item::TYPE_PERMISSION ? $item : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->getItems(Item::TYPE_ROLE);
    }

    /**
     * Set default roles
     * @param string[]|Closure $roles either array of roles or a callable returning it.
     *
     * @throws InvalidArgumentException when $roles is neither array nor Closure.
     * @throws InvalidValueException when Closure return is not an array.
     */
    public function setDefaultRoles(array|Closure $roles): void
    {
        if (is_array($roles)) {
            $this->defaultRoles = $roles;
        } elseif ($roles instanceof Closure) {
            $roles = call_user_func($roles);

            if (!is_array($roles)) {
                throw new InvalidValueException('Default roles closure must return an array');
            }

            $this->defaultRoles = $roles;
        } else {
            throw new InvalidArgumentException('Default roles must be either an array or a callable');
        }
    }

    /**
     * Get default roles.
     *
     * @return string[] default roles.
     */
    public function getDefaultRoles(): array
    {
        return $this->defaultRoles;
    }

    /**
     * Returns defaultRoles as array of Role objects.
     *
     * @return Role[] default roles. The array is indexed by the role names.
     */
    public function getDefaultRoleInstances(): array
    {
        $result = [];

        foreach ($this->defaultRoles as $roleName) {
            $result[$roleName] = $this->createRole($roleName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions(): array
    {
        return $this->getItems(Item::TYPE_PERMISSION);
    }

    /**
     * Executes the rule associated with the specified auth item.
     *
     * If the item does not specify a rule, this method will return true. Otherwise, it will return the value of
     * [[Rule::execute()]].
     *
     * @param string|int $user the user ID. This should be either an integer or a string representing.
     * the unique identifier of a user. See [[\yii\web\User::id]].
     * @param Item $item the auth item that needs to execute its rule.
     * @param array $params parameters passed to [[CheckAccessInterface::checkAccess()]] and will be passed to the rule.
     *
     * @return bool the return value of [[Rule::execute()]]. If the auth item does not specify a rule, true will be
     * returned.
     *
     * @throws InvalidConfigException if the auth item has an invalid rule.
     */
    protected function executeRule(string|int $user, Item $item, array $params): bool
    {
        if ($item->ruleName === null) {
            return true;
        }

        $rule = $this->getRule($item->ruleName);

        if ($rule instanceof Rule) {
            return $rule->execute($user, $item, $params);
        }

        throw new InvalidConfigException("Rule not found: {$item->ruleName}");
    }

    /**
     * Checks whether array of $assignments is empty and [[defaultRoles]] property is empty as well.
     *
     * @param Assignment[] $assignments array of user's assignments.
     *
     * @return bool whether array of $assignments is empty and [[defaultRoles]] property is empty as well.
     */
    protected function hasNoAssignments(array $assignments): bool
    {
        return empty($assignments) && empty($this->defaultRoles);
    }
}
