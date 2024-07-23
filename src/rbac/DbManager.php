<?php

declare(strict_types=1);

namespace yii\rbac;

use Psr\SimpleCache\CacheInterface;
use Stringable;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidCallException;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\di\Instance;

/**
 * DbManager represents an authorization manager that stores authorization information in database.
 *
 * The database connection is specified by [[db]]. The database schema could be initialized by applying migration:
 *
 * ```
 * yii migrate --migrationPath=@yii/rbac/migrations/
 * ```
 *
 * If you don't want to use migration and need SQL instead, files for all databases are in migrations directory.
 *
 * You may change the names of the tables used to store the authorization and rule data by setting [[itemTable]],
 * [[itemChildTable]], [[assignmentTable]] and [[ruleTable]].
 *
 * For more details and usage information on DbManager, see the [guide article on security authorization](guide:security-authorization).
 */
class DbManager extends BaseManager
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbManager object is created, if you want to change this property, you should only assign it with a DB
     * connection object.
     */
    public Connection|array|string $db = 'db';
    /**
     * @var string the name of the table storing authorization items. Defaults to "auth_item".
     */
    public string $itemTable = '{{%auth_item}}';
    /**
     * @var string the name of the table storing authorization item hierarchy. Defaults to "auth_item_child".
     */
    public string $itemChildTable = '{{%auth_item_child}}';
    /**
     * @var string the name of the table storing authorization item assignments. Defaults to "auth_assignment".
     */
    public string $assignmentTable = '{{%auth_assignment}}';
    /**
     * @var string the name of the table storing rules. Defaults to "auth_rule".
     */
    public string $ruleTable = '{{%auth_rule}}';
    /**
     * @var CacheInterface|array|string|null the cache used to improve RBAC performance. This can be one of the
     * following:
     *
     * - an application component ID (e.g. `cache`).
     * - a configuration array.
     * - a [[\yii\caching\Cache]] object.
     *
     * When this is not set, it means caching is not enabled.
     *
     * Note that by enabling RBAC cache, all auth items, rules and auth item parent-child relationships will be cached
     * and loaded into memory. This will improve the performance of RBAC permission check. However, it does require
     * extra memory and as a result may not be appropriate if your RBAC system contains too many auth items. You should
     * seek other RBAC implementations (e.g. RBAC based on Redis storage) in this case.
     *
     * Also note that if you modify RBAC items, rules or parent-child relationships from outside of this component,
     * you have to manually call [[invalidateCache()]] to ensure data consistency.
     */
    public CacheInterface|array|string|null $cache = null;
    /**
     * @var string the key used to store RBAC data in cache.
     *
     * @see \Psr\SimpleCache\CacheInterface::get()
     */
    public string $cacheKey = 'rbac';
    /**
     * @var string the key used to store user RBAC roles in cache.
     */
    public string $rolesCacheSuffix = 'roles';

    /**
     * @var Item[] all auth items (name => Item).
     */
    protected array $items = [];
    /**
     * @var Rule[] all auth rules (name => Rule).
     */
    protected array $rules = [];
    /**
     * @var array auth item parent-child relationships (childName => list of parents).
     */
    protected array $parents = [];
    /**
     * @var array user assignments (user id => Assignment[]).
     */
    protected array $checkAccessAssignments = [];

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by establishing the database connection.
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::class);

        if ($this->cache !== null) {
            $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkAccess(string|int|Stringable $userId, string $permissionName, array $params = []): bool
    {
        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        if (isset($this->checkAccessAssignments[(string) $userId])) {
            $assignments = $this->checkAccessAssignments[(string) $userId];
        } else {
            $assignments = $this->getAssignments($userId);
            $this->checkAccessAssignments[(string) $userId] = $assignments;
        }

        if ($this->hasNoAssignments($assignments)) {
            return false;
        }

        $this->loadFromCache();

        if ($this->items !== []) {
            return $this->checkAccessFromCache($userId, $permissionName, $params, $assignments);
        }

        return $this->checkAccessRecursive($userId, $permissionName, $params, $assignments);
    }

    /**
     * Performs access check for the specified user based on the data loaded from cache.
     * This method is internally called by [[checkAccess()]] when [[cache]] is enabled.
     *
     * @param string|int $user the user ID. This should can be either an integer or a string representing the unique
     * identifier of a user. See [[\yii\web\User::id]].
     * @param string $itemName the name of the operation that need access check.
     * @param array $params name-value pairs that would be passed to rules associated with the tasks and roles assigned
     * to the user. A param with name 'user' is added to this array, which holds the value of `$userId`.
     * @param Assignment[] $assignments the assignments to the specified user.
     *
     * @return bool whether the operations can be performed by the user.
     */
    protected function checkAccessFromCache(string|int $user, string $itemName, array $params, array $assignments): bool
    {
        if (!isset($this->items[$itemName])) {
            return false;
        }

        $item = $this->items[$itemName];

        Yii::debug($item instanceof Role ? "Checking role: $itemName" : "Checking permission: $itemName", __METHOD__);

        if (!$this->executeRule($user, $item, $params)) {
            return false;
        }

        if (isset($assignments[$itemName]) || in_array($itemName, $this->defaultRoles)) {
            return true;
        }

        if (!empty($this->parents[$itemName])) {
            foreach ($this->parents[$itemName] as $parent) {
                if ($this->checkAccessFromCache($user, $parent, $params, $assignments)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Performs access check for the specified user.
     * This method is internally called by [[checkAccess()]].
     *
     * @param string|int $user the user ID. This should can be either an integer or a string representing the unique
     * identifier of a user. See [[\yii\web\User::id]].
     * @param string $itemName the name of the operation that need access check.
     * @param array $params name-value pairs that would be passed to rules associated with the tasks and roles assigned
     * to the user. A param with name 'user' is added to this array, which holds the value of `$userId`.
     * @param Assignment[] $assignments the assignments to the specified user.
     *
     * @return bool whether the operations can be performed by the user.
     */
    protected function checkAccessRecursive(string|int $user, string $itemName, array $params, array $assignments): bool
    {
        if (($item = $this->getItem($itemName)) === null) {
            return false;
        }

        Yii::debug($item instanceof Role ? "Checking role: $itemName" : "Checking permission: $itemName", __METHOD__);

        if (!$this->executeRule($user, $item, $params)) {
            return false;
        }

        if (isset($assignments[$itemName]) || in_array($itemName, $this->defaultRoles)) {
            return true;
        }

        $query = new Query();
        
        $parents = $query
            ->select(['parent'])
            ->from($this->itemChildTable)
            ->where(['child' => $itemName])
            ->column($this->db);

        foreach ($parents as $parent) {
            if ($this->checkAccessRecursive($user, $parent, $params, $assignments)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItem(string $name): Item|null
    {
        if (empty($name)) {
            return null;
        }

        if (!empty($this->items[$name])) {
            return $this->items[$name];
        }

        $row = (new Query())->from($this->itemTable)->where(['name' => $name])->one($this->db);

        if ($row === false) {
            return null;
        }

        return $this->populateItem($row);
    }

    /**
     * Returns a value indicating whether the database supports cascading update and delete.
     * The default implementation will return false for SQLite database and true for all other databases.
     *
     * @return bool whether the database supports cascading update and delete.
     */
    protected function supportsCascadeUpdate(): bool
    {
        return strncmp($this->db->getDriverName(), 'sqlite', 6) !== 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function addItem(Item $item): bool
    {
        $time = time();

        if ($item->createdAt === null) {
            $item->createdAt = $time;
        }

        if ($item->updatedAt === null) {
            $item->updatedAt = $time;
        }

        $this->db->createCommand()
            ->insert(
                $this->itemTable,
                [
                    'name' => $item->name,
                    'type' => $item->type,
                    'description' => $item->description,
                    'rule_name' => $item->ruleName,
                    'data' => $item->data === null ? null : serialize($item->data),
                    'created_at' => $item->createdAt,
                    'updated_at' => $item->updatedAt,
                ],
            )->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeItem(Item $item): bool
    {
        if (!$this->supportsCascadeUpdate()) {
            $this->db->createCommand()
                ->delete(
                    $this->itemChildTable,
                    ['or', '[[parent]]=:parent', '[[child]]=:child'],
                    [':parent' => $item->name, ':child' => $item->name]
                )->execute();
            $this->db->createCommand()->delete($this->assignmentTable, ['item_name' => $item->name])->execute();
        }

        $this->db->createCommand()->delete($this->itemTable, ['name' => $item->name])->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateItem(string $name, Item $item): bool
    {
        if ($item->name !== $name && !$this->supportsCascadeUpdate()) {
            $this->db->createCommand()
                ->update($this->itemChildTable, ['parent' => $item->name], ['parent' => $name])
                ->execute();
            $this->db->createCommand()
                ->update($this->itemChildTable, ['child' => $item->name], ['child' => $name])
                ->execute();
            $this->db->createCommand()
                ->update($this->assignmentTable, ['item_name' => $item->name], ['item_name' => $name])
                ->execute();
        }

        $item->updatedAt = time();

        $this->db->createCommand()
            ->update(
                $this->itemTable,
                [
                    'name' => $item->name,
                    'description' => $item->description,
                    'rule_name' => $item->ruleName,
                    'data' => $item->data === null ? null : serialize($item->data),
                    'updated_at' => $item->updatedAt,
                ],
                [
                    'name' => $name,
                ],
            )->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRule(Rule $rule): bool
    {
        $time = time();

        if ($rule->createdAt === null) {
            $rule->createdAt = $time;
        }

        if ($rule->updatedAt === null) {
            $rule->updatedAt = $time;
        }

        $this->db->createCommand()
            ->insert(
                $this->ruleTable,
                [
                    'name' => $rule->name,
                    'data' => serialize($rule),
                    'created_at' => $rule->createdAt,
                    'updated_at' => $rule->updatedAt,
                ],
            )->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateRule(string $name, Rule $rule): bool
    {
        if ($rule->name !== $name && !$this->supportsCascadeUpdate()) {
            $this->db->createCommand()
                ->update($this->itemTable, ['rule_name' => $rule->name], ['rule_name' => $name])
                ->execute();
        }

        $rule->updatedAt = time();

        $this->db->createCommand()
            ->update(
                $this->ruleTable,
                [
                    'name' => $rule->name,
                    'data' => serialize($rule),
                    'updated_at' => $rule->updatedAt,
                ],
                [
                    'name' => $name,
                ],
            )->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function removeRule(Rule $rule): bool
    {
        if (!$this->supportsCascadeUpdate()) {
            $this->db->createCommand()
                ->update($this->itemTable, ['rule_name' => null], ['rule_name' => $rule->name])
                ->execute();
        }

        $this->db->createCommand()->delete($this->ruleTable, ['name' => $rule->name])->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems(int $type): array
    {
        $query = (new Query())->from($this->itemTable)->where(['type' => $type]);

        $items = [];

        foreach ($query->all($this->db) as $row) {
            $items[$row['name']] = $this->populateItem($row);
        }

        return $items;
    }

    /**
     * Populates an auth item with the data fetched from database.
     *
     * @param array $row the data from the auth item table.
     *
     * @return Item the populated auth item instance (either Role or Permission).
     */
    protected function populateItem($row)
    {
        $class = $row['type'] == Item::TYPE_PERMISSION ? Permission::className() : Role::className();

        if (
            !isset($row['data']) ||
            ($data = @unserialize(is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data'])) === false
        ) {
            $data = null;
        }

        return new $class(
            [
                'name' => $row['name'],
                'type' => $row['type'],
                'description' => $row['description'],
                'ruleName' => $row['rule_name'] ?: null,
                'data' => $data,
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ],
        );
    }

    /**
     * {@inheritdoc}
     * The roles returned by this method include the roles assigned via [[$defaultRoles]].
     */
    public function getRolesByUser(string|int|Stringable $userId): array
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        if ($this->cache !== null) {
            $data = $this->cache->get($this->getUserRolesCacheKey((string) $userId), false);

            if ($data !== false) {
                return $data;
            }
        }

        $query = (new Query())
            ->select('b.*')
            ->from(['a' => $this->assignmentTable, 'b' => $this->itemTable])
            ->where('{{a}}.[[item_name]]={{b}}.[[name]]')
            ->andWhere(['a.user_id' => (string) $userId])
            ->andWhere(['b.type' => Item::TYPE_ROLE]);

        $roles = $this->getDefaultRoleInstances();

        foreach ($query->all($this->db) as $row) {
            $roles[$row['name']] = $this->populateItem($row);
        }

        if ($this->cache !== null) {
            $this->cacheUserRolesData((string) $userId, $roles);
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildRoles(string $roleName): array
    {
        $role = $this->getRole($roleName);

        if ($role === null) {
            throw new InvalidArgumentException("Role \"$roleName\" not found.");
        }

        $result = [];

        $this->getChildrenRecursive($roleName, $this->getChildrenList(), $result);

        $roles = [$roleName => $role];

        $roles += array_filter(
            $this->getRoles(),
            static function (Role $roleItem) use ($result): bool {
                return array_key_exists($roleItem->name, $result);
            }
        );

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionsByRole(string $roleName): array
    {
        $childrenList = $this->getChildrenList();
        $result = [];

        $this->getChildrenRecursive($roleName, $childrenList, $result);

        if (empty($result)) {
            return [];
        }

        $query = (new Query())
            ->from($this->itemTable)
            ->where(['type' => Item::TYPE_PERMISSION, 'name' => array_keys($result)]);

        $permissions = [];

        foreach ($query->all($this->db) as $row) {
            $permissions[$row['name']] = $this->populateItem($row);
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionsByUser(string|int|Stringable $userId): array
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        $directPermission = $this->getDirectPermissionsByUser($userId);
        $inheritedPermission = $this->getInheritedPermissionsByUser($userId);

        return array_merge($directPermission, $inheritedPermission);
    }

    /**
     * Returns all permissions that are directly assigned to user.
     *
     * @param string|int $userId the user ID (see [[\yii\web\User::id]]).
     *
     * @return Permission[] all direct permissions that the user has. The array is indexed by the permission names.
     */
    protected function getDirectPermissionsByUser(string|int $userId): array
    {
        $query = (new Query())
            ->select('b.*')
            ->from(['a' => $this->assignmentTable, 'b' => $this->itemTable])
            ->where('{{a}}.[[item_name]]={{b}}.[[name]]')
            ->andWhere(['a.user_id' => (string) $userId])
            ->andWhere(['b.type' => Item::TYPE_PERMISSION]);

        $permissions = [];

        foreach ($query->all($this->db) as $row) {
            $permissions[$row['name']] = $this->populateItem($row);
        }

        return $permissions;
    }

    /**
     * Returns all permissions that the user inherits from the roles assigned to him.
     *
     * @param string|int $userId the user ID (see [[\yii\web\User::id]]).
     *
     * @return Permission[] all inherited permissions that the user has. The array is indexed by the permission names.
     */
    protected function getInheritedPermissionsByUser(string|int $userId): array
    {
        $query = (new Query())
            ->select('item_name')
            ->from($this->assignmentTable)
            ->where(['user_id' => (string) $userId]);

        $childrenList = $this->getChildrenList();

        $result = [];

        foreach ($query->column($this->db) as $roleName) {
            $this->getChildrenRecursive($roleName, $childrenList, $result);
        }

        if (empty($result)) {
            return [];
        }

        $query = (new Query())
            ->from($this->itemTable)
            ->where(['type' => Item::TYPE_PERMISSION, 'name' => array_keys($result)]);

        $permissions = [];

        foreach ($query->all($this->db) as $row) {
            $permissions[$row['name']] = $this->populateItem($row);
        }

        return $permissions;
    }

    /**
     * Returns the children for every parent.
     *
     * @return array the children list. Each array key is a parent item name, and the corresponding array value is a
     * list of child item names.
     */
    protected function getChildrenList(): array
    {
        $query = (new Query())->from($this->itemChildTable);

        $parents = [];

        foreach ($query->all($this->db) as $row) {
            $parents[$row['parent']][] = $row['child'];
        }

        return $parents;
    }

    /**
     * Recursively finds all children and grand children of the specified item.
     *
     * @param string $name the name of the item whose children are to be looked for.
     * @param array $childrenList the child list built via [[getChildrenList()]].
     * @param array $result the children and grand children (in array keys).
     */
    protected function getChildrenRecursive(string $name, array $childrenList, array &$result): void
    {
        if (isset($childrenList[$name])) {
            foreach ($childrenList[$name] as $child) {
                $result[$child] = true;
                $this->getChildrenRecursive($child, $childrenList, $result);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRule(string $name): Rule|null
    {
        if ($this->rules !== []) {
            return isset($this->rules[$name]) ? $this->rules[$name] : null;
        }

        $row = (new Query())->select(['data'])->from($this->ruleTable)->where(['name' => $name])->one($this->db);

        if ($row === false) {
            return null;
        }

        $data = $row['data'];

        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        if (!$data) {
            return null;
        }

        return unserialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getRules(): array
    {
        if ($this->rules !== []) {
            return $this->rules;
        }

        $query = (new Query())->from($this->ruleTable);

        $rules = [];
        foreach ($query->all($this->db) as $row) {
            $data = $row['data'];
            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }
            if ($data) {
                $rules[$row['name']] = unserialize($data);
            }
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignment(string $roleName, string|int|Stringable $userId): Assignment|null
    {
        if ($this->isEmptyUserId($userId)) {
            return null;
        }

        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        $row = (new Query())
            ->from($this->assignmentTable)
            ->where(['user_id' => (string) $userId, 'item_name' => $roleName])
            ->one($this->db);

        if ($row === false) {
            return null;
        }

        return new Assignment(
            [
                'userId' => $row['user_id'],
                'roleName' => $row['item_name'],
                'createdAt' => $row['created_at'],
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignments(string|int|Stringable $userId): array
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        $query = (new Query())->from($this->assignmentTable)->where(['user_id' => (string) $userId]);

        $assignments = [];

        foreach ($query->all($this->db) as $row) {
            $assignments[$row['item_name']] = new Assignment(
                [
                    'userId' => $row['user_id'],
                    'roleName' => $row['item_name'],
                    'createdAt' => $row['created_at'],
                ],
            );
        }

        return $assignments;
    }

    /**
     * {@inheritdoc}
     */
    public function canAddChild(Item $parent, Item $child): bool
    {
        return !$this->detectLoop($parent, $child);
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(Item $parent, Item $child): bool
    {
        if ($parent->name === $child->name) {
            throw new InvalidArgumentException("Cannot add '{$parent->name}' as a child of itself.");
        }

        if ($parent instanceof Permission && $child instanceof Role) {
            throw new InvalidArgumentException('Cannot add a role as a child of a permission.');
        }

        if ($this->detectLoop($parent, $child)) {
            throw new InvalidCallException(
                "Cannot add '{$child->name}' as a child of '{$parent->name}'. A loop has been detected."
            );
        }

        $this->db->createCommand()
            ->insert($this->itemChildTable, ['parent' => $parent->name, 'child' => $child->name])
            ->execute();

        $this->invalidateCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild(Item $parent, Item $child): bool
    {
        $result = $this->db->createCommand()
            ->delete($this->itemChildTable, ['parent' => $parent->name, 'child' => $child->name])
            ->execute() > 0;

        $this->invalidateCache();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChildren(Item $parent): bool
    {
        $result = $this->db->createCommand()
            ->delete($this->itemChildTable, ['parent' => $parent->name])
            ->execute() > 0;

        $this->invalidateCache();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild(Item $parent, Item $child): bool
    {
        return (new Query())
            ->from($this->itemChildTable)
            ->where(['parent' => $parent->name, 'child' => $child->name])
            ->one($this->db) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(string $name): array
    {
        $query = (new Query())
            ->select(['name', 'type', 'description', 'rule_name', 'data', 'created_at', 'updated_at'])
            ->from([$this->itemTable, $this->itemChildTable])
            ->where(['parent' => $name, 'name' => new Expression('[[child]]')]);

        $children = [];

        foreach ($query->all($this->db) as $row) {
            $children[$row['name']] = $this->populateItem($row);
        }

        return $children;
    }

    /**
     * Checks whether there is a loop in the authorization item hierarchy.
     *
     * @param Item $parent the parent item.
     * @param Item $child the child item to be added to the hierarchy.
     *
     * @return bool whether a loop exists.
     */
    protected function detectLoop(Item $parent, Item $child): bool
    {
        if ($child->name === $parent->name) {
            return true;
        }

        foreach ($this->getChildren($child->name) as $grandchild) {
            if ($this->detectLoop($parent, $grandchild)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function assign(Role|Permission $role, string|int $userId): Assignment
    {
        $assignment = new Assignment(
            [
                'userId' => $userId,
                'roleName' => $role->name,
                'createdAt' => time(),
            ]
        );

        $this->db->createCommand()
            ->insert(
                $this->assignmentTable,
                [
                    'user_id' => $assignment->userId,
                    'item_name' => $assignment->roleName,
                    'created_at' => $assignment->createdAt,
                ],
            )->execute();

        unset($this->checkAccessAssignments[(string) $userId]);

        $this->invalidateCache();

        return $assignment;
    }

    /**
     * {@inheritdoc}
     */
    public function revoke(Role|Permission $role, string|int|Stringable $userId): bool
    {
        if ($this->isEmptyUserId($userId)) {
            return false;
        }

        if ($userId instanceof Stringable) {
            $userId = (string) $userId;
        }

        unset($this->checkAccessAssignments[(string) $userId]);

        $result = $this->db->createCommand()
            ->delete($this->assignmentTable, ['user_id' => (string) $userId, 'item_name' => $role->name])
            ->execute() > 0;

        $this->invalidateCache();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAll(mixed $userId): bool
    {
        if ($this->isEmptyUserId($userId)) {
            return false;
        }

        unset($this->checkAccessAssignments[(string) $userId]);

        $result = $this->db->createCommand()
            ->delete($this->assignmentTable, ['user_id' => (string) $userId])
            ->execute() > 0;

        $this->invalidateCache();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll(): void
    {
        $this->removeAllAssignments();
        $this->db->createCommand()->delete($this->itemChildTable)->execute();
        $this->db->createCommand()->delete($this->itemTable)->execute();
        $this->db->createCommand()->delete($this->ruleTable)->execute();
        $this->invalidateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllPermissions(): void
    {
        $this->removeAllItems(Item::TYPE_PERMISSION);
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllRoles(): void
    {
        $this->removeAllItems(Item::TYPE_ROLE);
    }

    /**
     * Removes all auth items of the specified type.
     *
     * @param int $type the auth item type (either Item::TYPE_PERMISSION or Item::TYPE_ROLE).
     */
    protected function removeAllItems(int $type): void
    {
        if (!$this->supportsCascadeUpdate()) {
            $names = (new Query())
                ->select(['name'])
                ->from($this->itemTable)
                ->where(['type' => $type])
                ->column($this->db);

            if (empty($names)) {
                return;
            }

            $key = $type == Item::TYPE_PERMISSION ? 'child' : 'parent';

            $this->db->createCommand()->delete($this->itemChildTable, [$key => $names])->execute();
            $this->db->createCommand()->delete($this->assignmentTable, ['item_name' => $names])->execute();
        }

        $this->db->createCommand()->delete($this->itemTable, ['type' => $type])->execute();

        $this->invalidateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllRules(): void
    {
        if (!$this->supportsCascadeUpdate()) {
            $this->db->createCommand()->update($this->itemTable, ['rule_name' => null])->execute();
        }

        $this->db->createCommand()->delete($this->ruleTable)->execute();

        $this->invalidateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllAssignments(): void
    {
        $this->checkAccessAssignments = [];
        $this->db->createCommand()->delete($this->assignmentTable)->execute();
    }

    public function invalidateCache(): void
    {
        if ($this->cache !== null) {
            $this->cache->delete($this->cacheKey);
            $this->items = [];
            $this->rules = [];
            $this->parents = [];

            $cachedUserIds = $this->cache->get($this->getUserRolesCachedSetKey(), false);

            if ($cachedUserIds !== false) {
                foreach ($cachedUserIds as $userId) {
                    $this->cache->delete($this->getUserRolesCacheKey($userId));
                }

                $this->cache->delete($this->getUserRolesCachedSetKey());
            }
        }

        $this->checkAccessAssignments = [];
    }

    public function loadFromCache(): void
    {
        if ($this->items !== [] || !$this->cache instanceof CacheInterface) {
            return;
        }

        $data = $this->cache->get($this->cacheKey, false);

        if (is_array($data) && isset($data[0], $data[1], $data[2])) {
            [$this->items, $this->rules, $this->parents] = $data;

            return;
        }

        $query = (new Query())->from($this->itemTable);

        $this->items = [];

        foreach ($query->all($this->db) as $row) {
            $this->items[$row['name']] = $this->populateItem($row);
        }

        $query = (new Query())->from($this->ruleTable);

        $this->rules = [];

        foreach ($query->all($this->db) as $row) {
            $data = $row['data'];

            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }

            if ($data) {
                $this->rules[$row['name']] = unserialize($data);
            }
        }

        $query = (new Query())->from($this->itemChildTable);

        $this->parents = [];

        foreach ($query->all($this->db) as $row) {
            if (isset($this->items[$row['child']])) {
                $this->parents[$row['child']][] = $row['parent'];
            }
        }

        $this->cache->set($this->cacheKey, [$this->items, $this->rules, $this->parents]);
    }

    /**
     * Returns all role assignment information for the specified role.
     *
     * @param string $roleName the role name.
     *
     * @return string[] the ids. An empty array will be returned if role is not assigned to any user.
     */
    public function getUserIdsByRole(string $roleName): array
    {
        if (empty($roleName)) {
            return [];
        }

        return (new Query())
            ->select('[[user_id]]')
            ->from($this->assignmentTable)
            ->where(['item_name' => $roleName])
            ->column($this->db);
    }

    /**
     * Check whether $userId is empty.
     *
     * @param mixed $userId the user ID.
     *
     * @return bool whether $userId is empty.
     */
    protected function isEmptyUserId(mixed $userId): bool
    {
        return !isset($userId) || $userId === '';
    }

    private function getUserRolesCacheKey(string $userId): string
    {
        return $this->cacheKey . $this->rolesCacheSuffix . $userId;
    }

    private function getUserRolesCachedSetKey(): string
    {
        return $this->cacheKey . $this->rolesCacheSuffix;
    }

    private function cacheUserRolesData(string $userId, array $roles): void
    {
        $cachedUserIds = $this->cache->get($this->getUserRolesCachedSetKey(), false);

        if ($cachedUserIds === false) {
            $cachedUserIds = [];
        }

        $cachedUserIds[] = $userId;

        $this->cache->set($this->getUserRolesCacheKey($userId), $roles);
        $this->cache->set($this->getUserRolesCachedSetKey(), $cachedUserIds);
    }
}
