<?php

declare(strict_types=1);

namespace yii\di;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\base\Configurable;

use function array_key_exists;
use function array_merge;
use function array_values;
use function count;
use function gettype;
use function is_array;
use function is_callable;
use function is_int;
use function is_object;
use function is_string;
use function key;
use function str_contains;

/**
 * Container implements a [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * A dependency injection (DI) container is an object that knows how to instantiate and configure objects and all their
 * dependent objects.
 *
 * For more information about DI, please refer to [Martin Fowler's article](https://martinfowler.com/articles/injection.html).
 *
 * Container supports constructor injection as well as property injection.
 *
 * To use Container, you first need to set up the class dependencies by calling [[set()]].
 *
 * You then call [[get()]] to create a new class object. The Container will automatically instantiate dependent objects,
 * inject them into the object being created, configure, and finally return the newly created object.
 *
 * By default, [[\Yii::$container]] refers to a Container instance which is used by [[\Yii::createObject()]] to create
 * new object instances. You may use this method to replace the `new` operator when creating a new object, which gives
 * you the benefit of automatic dependency resolution and default property configuration.
 *
 * Below is an example of using Container:
 *
 * ```php
 * namespace app\models;
 *
 * use yii\base\BaseObject;
 * use yii\db\Connection;
 * use yii\di\Container;
 *
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder extends BaseObject implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister extends BaseObject
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('yii\db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     'class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * // which is equivalent to:
 *
 * $db = new \yii\db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 * ```
 *
 * For more details and usage information on Container, see the [guide article on di-containers](guide:concept-di-container).
 *
 * @property-read array $definitions The list of the object definitions or the loaded shared objects (type or
 * ID => definition or instance).
 * @property-write bool $resolveArrays Whether to attempt to resolve elements in array dependencies.
 */
class Container extends Component implements ContainerInterface
{
    /**
     * @var array singleton objects indexed by their types
     */
    private array $_singletons = [];
    /**
     * @var array object definitions indexed by their types
     */
    private array $_definitions = [];
    /**
     * @var array constructor parameters indexed by object types
     */
    private array $_params = [];
    /**
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private array $_reflections = [];
    /**
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private array $_dependencies = [];
    /**
     * @var bool whether to attempt to resolve elements in array dependencies
     */
    private bool $_resolveArrays = false;

    /**
     * Returns an instance of the requested class.
     *
     * You may provide constructor parameters (`$params`) and object configurations (`$config`) that will be used during
     * the creation of the instance.
     *
     * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last parameter
     * to the class constructor; Otherwise, the configuration will be applied *after* the object is instantiated.
     *
     * Note that if the class is declared to be singleton by calling [[setSingleton()]], the same instance of the class
     * will be returned each time this method is called.
     *
     * In this case, the constructor parameters and object configurations will be used only if the class is instantiated
     * the first time.
     *
     * @param string|Instance $id the class Instance, name, or an alias name (e.g. `foo`) that was previously
     * registered via [[set()]] or [[setSingleton()]].
     * @param array $params a list of constructor parameter values. Use one of two definitions:
     *  - Parameters as name-value pairs, for example, `['posts' => PostRepository::class]`.
     *  - Parameters in the order they appear in the constructor declaration. If you want to skip some parameters,
     *    you should index the remaining ones with the integers that represent their positions in the constructor
     *    parameter list.
     *    Dependencies indexed by name and by position in the same array are not allowed.
     * @param array $config a list of name-value pairs that will be used to initialize the object properties.
     *
     * @return object an instance of the requested class.
     *
     * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9).
     * @throws Throwable in case of circular references.
     */
    public function get(string|Instance $id, array $params = [], array $config = []): object
    {
        if ($id instanceof Instance) {
            $id = $id->id;
        }

        if (isset($this->_singletons[$id])) {
            // singleton
            return $this->_singletons[$id];
        }

        if (!isset($this->_definitions[$id])) {
            return $this->build($id, $params, $config);
        }

        $definition = $this->_definitions[$id];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($id, $params));
            $object = $definition($this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($id, $params);

            if ($concrete === $id) {
                $object = $this->build($id, $params, $config);
            } else {
                $object = $this->get($concrete, $params, $config);
            }

        } elseif (is_object($definition)) {
            return $this->_singletons[$id] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($id, $this->_singletons)) {
            // singleton
            $this->_singletons[$id] = $object;
        }

        return $object;
    }

    /**
     * Registers a class definition with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('yii\db\Connection');
     *
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->set('foo', 'yii\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->set('yii\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->set('db', function ($container, $params, $config) {
     *     return new \yii\db\Connection($config);
     * });
     * ```
     *
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. It can be one of the following:
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable should
     *   be `function ($container, $params, $config)`, where `$params` stands for the list of constructor parameters,
     *   `$config` the object configuration, and `$container` the container object. The return value of the callable
     *   will be returned by [[get()]] as the object instance requested.
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property values
     *   of the newly created object when [[get()]] is called. The `class` element stands for
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
     * - a string: a class name, an interface name or an alias name.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class constructor
     * when [[get()]] is called.
     *
     * @return $this the container itself.
     *
     * @throws InvalidConfigException if the definition is invalid.
     */
    public function set(string $class, mixed $definition = [], array $params = []): static
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;

        unset($this->_singletons[$class]);

        return $this;
    }

    /**
     * Registers a class definition with this container and marks the class as a singleton class.
     *
     * This method is similar to [[set()]] except that classes registered via this method will only have one instance.
     *
     * Each time [[get()]] is called, the same instance of the specified class will be returned.
     *
     * @param string $class class name, interface name or alias name.
     * @param mixed $definition the definition associated with `$class`. See [[set()]] for more details.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class constructor
     * when [[get()]] is called.
     *
     * @return $this the container itself.
     *
     * @throws InvalidConfigException if the definition is invalid.
     *
     * @see set()
     */
    public function setSingleton(string $class, mixed $definition = [], array $params = []): static
    {
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        $this->_params[$class] = $params;
        $this->_singletons[$class] = null;

        return $this;
    }

    /**
     * Returns a value indicating whether the container has the definition of the specified name.
     *
     * @param string $id class name, interface name or alias name.
     *
     * @return bool Whether the container has the definition of the specified name.
     *
     * @see set()
     */
    public function has(string $id): bool
    {
        return isset($this->_definitions[$id]);
    }

    /**
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     *
     * @param string $class class name, interface name or alias name.
     * @param bool $checkInstance whether to check if the singleton has been instantiated.
     *
     * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true, the
     * method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton(string $class, bool $checkInstance = false): bool
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    /**
     * Removes the definition for the specified name.
     *
     * @param string $class class name, interface name or alias name.
     */
    public function clear(string $class): void
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    /**
     * Normalizes the class definition.
     *
     * @param string $class class name.
     * @param callable|array|string|object $definition the class definition.
     *
     * @return callable|array|string|object the normalized class definition.
     *
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function normalizeDefinition(
        string $class,
        callable|array|string|object|int $definition,
    ): callable|array|string|object {
        if (empty($definition)) {
            return ['class' => $class];
        }

        if (is_string($definition)) {
            return ['class' => $definition];
        }

        if ($definition instanceof Instance) {
            return ['class' => $definition->id];
        }

        if (is_callable($definition, true) || is_object($definition)) {
            return $definition;
        }

        if (is_array($definition)) {
            if (!isset($definition['class']) && isset($definition['__class'])) {
                $definition['class'] = $definition['__class'];
                unset($definition['__class']);
            }
            if (!isset($definition['class'])) {
                if (str_contains($class, '\\')) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException('A class definition requires a "class" member.');
                }
            }

            return $definition;
        }

        throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
    }

    /**
     * Returns the list of the object definitions or the loaded shared objects.
     *
     * @return array the list of the object definitions or the loaded shared objects (type or ID => definition or
     * instance).
     */
    public function getDefinitions(): array
    {
        return $this->_definitions;
    }

    /**
     * Creates an instance of the specified class.
     *
     * This method will resolve dependencies of the specified class, instantiate them, and inject them into the new
     * instance of the specified class.
     *
     * @param string $class the class name.
     * @param array $params constructor parameters.
     * @param array $config configurations to be applied to the new instance.
     *
     * @return object the newly created instance of the specified class.
     *
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9).
     * @throws ReflectionException if the class cannot be reflected.
     * @throws InvalidConfigException|Throwable if a dependency cannot be resolved or if a dependency cannot be
     * fulfilled.
     */
    protected function build(string $class, array $params, array $config): object
    {
        /* @var $reflection ReflectionClass */
        [$reflection, $dependencies] = $this->getDependencies($class);

        $addDependencies = [];

        if (isset($config['__construct()'])) {
            $addDependencies = $config['__construct()'];
            unset($config['__construct()']);
        }

        foreach ($params as $index => $param) {
            $addDependencies[$index] = $param;
        }

        $this->validateDependencies($addDependencies);

        if ($addDependencies && is_int(key($addDependencies))) {
            $dependencies = array_values($dependencies);
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
        } else {
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
            $dependencies = array_values($dependencies);
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);

        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        $config = $this->resolveDependencies($config);

        if (!empty($dependencies) && $reflection->implementsInterface(Configurable::class)) {
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;

            return $reflection->newInstanceArgs($dependencies);
        }

        $object = $reflection->newInstanceArgs($dependencies);

        if ($object === null) {
            throw new InvalidConfigException('Failed to instantiate component or class "' . $class . '".');
        }

        foreach ($config as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * Merges two arrays into one.
     *
     * @param array $a the array to be merged to.
     * @param array $b the array to be merged from.
     *
     * @return array the merged array (the original arrays are not changed.)
     */
    private function mergeDependencies(array $a, array $b): array
    {
        foreach ($b as $index => $dependency) {
            $a[$index] = $dependency;
        }

        return $a;
    }

    /**
     * Validates dependencies.
     *
     * @param array $parameters the parameters to validate.
     *
     * @throws InvalidConfigException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     */
    private function validateDependencies(array $parameters): void
    {
        $hasStringParameter = false;
        $hasIntParameter = false;

        foreach ($parameters as $index => $parameter) {
            if (is_string($index)) {
                $hasStringParameter = true;
                if ($hasIntParameter) {
                    break;
                }
            } else {
                $hasIntParameter = true;
                if ($hasStringParameter) {
                    break;
                }
            }
        }

        if ($hasIntParameter && $hasStringParameter) {
            throw new InvalidConfigException(
                'Dependencies indexed by name and by position in the same array are not allowed.'
            );
        }
    }

    /**
     * Merges the user-specified constructor parameters with the ones registered via [[set()]].
     *
     * @param string $class class name, interface name or alias name.
     * @param array $params the constructor parameters.
     *
     * @return array the merged parameters.
     */
    protected function mergeParams(string $class, array $params): array
    {
        if (empty($this->_params[$class])) {
            return $params;
        }

        if (empty($params)) {
            return $this->_params[$class];
        }

        $ps = $this->_params[$class];
        foreach ($params as $index => $value) {
            $ps[$index] = $value;
        }

        return $ps;
    }

    /**
     * Returns the dependencies of the specified class.
     *
     * @param string $class class name, interface name or alias name.
     *
     * @return array the dependencies of the specified class.
     *
     * @throws NotInstantiableException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     */
    protected function getDependencies(string $class): array
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new NotInstantiableException(
                $class,
                'Failed to instantiate component or class "' . $class . '".',
                0,
                $e
            );
        }

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isVariadic()) {
                    break;
                }

                $c = $param->getType();
                $isClass = false;

                if ($c instanceof ReflectionNamedType) {
                    $isClass = !$c->isBuiltin();
                }

                $className = $isClass ? $c->getName() : null;

                if ($className !== null) {
                    $dependencies[$param->getName()] = Instance::of($className, $this->isNulledParam($param));
                } else {
                    $dependencies[$param->getName()] = $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : null;
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * Checks if a parameter is nulled.
     *
     * @param ReflectionParameter $param the parameter.
     *
     * @return bool
     */
    private function isNulledParam(ReflectionParameter $param): bool
    {
        return $param->isOptional() || $param->getType()?->allowsNull();
    }

    /**
     * Resolves dependencies by replacing them with the actual object instances.
     *
     * @param array $dependencies the dependencies.
     * @param ReflectionClass|null $reflection the class reflection associated with the dependencies.
     *
     * @return array the resolved dependencies.
     *
     * @throws InvalidConfigException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     * @throws Throwable in case of circular references.
     */
    protected function resolveDependencies(array $dependencies, ReflectionClass $reflection = null): array
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $dependency->get($this);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()?->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            } elseif ($this->_resolveArrays && is_array($dependency)) {
                $dependencies[$index] = $this->resolveDependencies($dependency, $reflection);
            }
        }

        return $dependencies;
    }

    /**
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This method allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allows calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * Yii::$container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function.
     * This can be either a list of parameters or an associative array representing named function parameters.
     *
     * @return mixed the callback return value.
     *
     * @throws InvalidConfigException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @throws ReflectionException|Throwable if the callback is not valid, callable.
     */
    public function invoke(callable $callback, array $params = []): mixed
    {
        return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
    }

    /**
     * Resolve dependencies for a function.
     *
     * This method can be used to implement similar functionality as provided by [[invoke()]] in other components.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function can be either numeric or associative.
     *
     * @return array The resolved dependencies.
     *
     * @throws InvalidConfigException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @throws ReflectionException|Throwable if the callback is not valid, callable.
     */
    public function resolveCallableDependencies(callable $callback, array $params = []): array
    {
        if (is_array($callback)) {
            $reflection = new ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_object($callback) && !$callback instanceof Closure) {
            $reflection = new ReflectionMethod($callback, '__invoke');
        } else {
            $reflection = new ReflectionFunction($callback);
        }

        $args = [];
        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
                $isClass = false;

                foreach ($type->getTypes() as $singleType) {
                    if (!$singleType->isBuiltin()) {
                        $type = $singleType;
                        $isClass = true;
                        break;
                    }
                }
            } else {
                $isClass = $type !== null && !$type->isBuiltin();
            }

            if ($isClass) {
                $className = $type->getName();
                if ($param->isVariadic()) {
                    $args = array_merge($args, array_values($params));
                    break;
                }

                if ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                } elseif (isset(Yii::$app) && Yii::$app->has($name) && ($obj = Yii::$app->get($name)) instanceof $className) {
                    $args[] = $obj;
                } else {
                    // If the argument is optional, we catch not instantiable exceptions
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }
                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }

        return $args;
    }

    /**
     * Registers class definitions within this container.
     *
     * @param array $definitions array of definitions. There are two allowed formats of an array.
     * The first format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: the definition associated with `$class`. Possible values are described in
     *    [[set()]] documentation for the `$definition` parameter. It Will be passed to the [[set()]] method
     *    as the second argument `$definition`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'yii\web\Request' => 'app\components\Request',
     *     'yii\web\Response' => [
     *         'class' => 'app\components\Response',
     *         'format' => 'json'
     *     ],
     *     'foo\Bar' => function () {
     *         $qux = new Qux;
     *         $foo = new Foo($qux);
     *         return new Bar($foo);
     *     }
     * ]);
     * ```
     *
     * The second format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: array of two elements. The first element will be passed the [[set()]] method as the
     *    second argument `$definition`, the second one — as `$params`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'foo\Bar' => [
     *          ['class' => 'app\Bar'],
     *          [Instance::of('baz')]
     *      ]
     * ]);
     * ```
     *
     * @throws InvalidConfigException if a definition is invalid.
     *
     * @see set() to know more about possible values of definitions
     */
    public function setDefinitions(array $definitions): void
    {
        foreach ($definitions as $class => $definition) {
            if (is_array($definition) && count($definition) === 2 && array_is_list($definition) && is_array($definition[1])) {
                $this->set($class, $definition[0], $definition[1]);
            } elseif (is_string($class)) {
                $this->set($class, $definition);
            }
        }
    }

    /**
     * Registers class definitions as singletons within this container by calling [[setSingleton()]].
     *
     * @param array $singletons array of singleton definitions. See [[setDefinitions()]] for allowed formats of an array.
     *
     * @throws InvalidConfigException if a definition is invalid.
     *
     * @see setSingleton() to know more about possible values of definitions
     * @see setDefinitions() for allowed formats of $singletons parameter
     */
    public function setSingletons(array $singletons): void
    {
        foreach ($singletons as $class => $definition) {
            if (is_array($definition) && count($definition) === 2 && array_is_list($definition)) {
                $this->setSingleton($class, $definition[0], $definition[1]);
                continue;
            }

            $this->setSingleton($class, $definition);
        }
    }

    /**
     * Returns whether to attempt to resolve elements in array dependencies.
     *
     * @param bool $value whether to attempt to resolve elements in array dependencies.
     */
    public function setResolveArrays(bool $value): void
    {
        $this->_resolveArrays = $value;
    }
}
