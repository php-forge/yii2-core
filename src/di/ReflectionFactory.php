<?php

namespace yii\di;

use Closure;
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
use yii\base\{Configurable, InvalidConfigException};
use yii\helpers\ArrayHelper;

class ReflectionFactory
{
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

    public function __construct(private Container $container) {
    }

    public function canBeAutowired(string $id): bool
    {
        try {
            $reflection = $this->getReflection($id);

            return $reflection->isInstantiable();
        } catch (ReflectionException $e) {
            return false;
        }
    }

    public function create(string $class, array $params = [], array $config = []): object
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

        if ($addDependencies && \is_int(\key($addDependencies))) {
            $dependencies = \array_values($dependencies);
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
        } else {
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
            $dependencies = \array_values($dependencies);
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
            $dependencies[\count($dependencies) - 1] = $config;

            return $reflection->newInstanceArgs($dependencies);
        }

        $object = $reflection->newInstanceArgs($dependencies);

        foreach ($config as $name => $value) {
            if (\str_ends_with($name, '()')) {
                $setter = \call_user_func_array([$object, \substr($name, 0, -2)], $value);

                if ($setter instanceof $object) {
                    $object = $setter;
                }
            } else {
                $object->$name = $value;
            }
        }

        return $object;
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
    public function resolveDependencies(array $dependencies, ReflectionClass $reflection = null): array
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                try {
                    $dependencies[$index] = $dependency->get($this->container);
                } catch (\Exception|\Throwable $e) {
                    if ($reflection !== null) {
                        $name = $reflection->getConstructor()?->getParameters()[$index]->getName();
                        $class = $reflection->getName();

                        throw new NotInstantiableException(
                            "Missing required parameter \"$name\" when instantiating \"$class\".",
                            0,
                            $e
                        );
                    }
                    throw $e;
                }
            } elseif ($this->_resolveArrays && \is_array($dependency)) {
                $dependencies[$index] = $this->resolveDependencies($dependency, $reflection);
            }
        }

        return $dependencies;
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
                        $args[] = $this->container->get($className);
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
     * Returns the dependencies of the specified class.
     *
     * @param string $class class name, interface name or alias name.
     *
     * @return array the dependencies of the specified class.
     *
     * @throws NotInstantiableException if a dependency cannot be resolved, or if a dependency cannot be fulfilled.
     */
    private function getDependencies(string $class): array
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new NotInstantiableException(
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

    private function getReflection(string $class): ReflectionClass
    {
        return $this->_reflections[$class] ?? new ReflectionClass($class);
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
            if (\is_string($index)) {
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
}
