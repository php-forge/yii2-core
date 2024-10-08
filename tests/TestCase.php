<?php

declare(strict_types=1);

namespace yiiunit;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the base class for all yii framework unit tests.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public static $params;

    /**
     * Clean up after test case.
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        $logger = Yii::getLogger();
        $logger->flush();
    }

    /**
     * Returns a test configuration param from /data/config.php.
     * @param string $name params name
     * @param mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/data/config.php';
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application.
     *
     * The application will be destroyed on tearDown() automatically.
     *
     * @param array $config The application configuration, if needed.
     * @param string $appClass name of the application class to create.
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application'): void
    {
        new $appClass(
            ArrayHelper::merge(
                [
                    'id' => 'testapp',
                    'basePath' => __DIR__,
                    'vendorPath' => $this->getVendorPath(),
                ],
                $config,
            )
        );
    }

    /**
     * Populates Yii::$app with a new web application.
     *
     * The application will be destroyed on tearDown() automatically.
     *
     * @param array $config The application configuration, if needed.
     * @param string $appClass name of the application class to create.
     */
    protected function mockWebApplication($config = [], $appClass = '\yii\web\Application'): void
    {
        new $appClass(
            ArrayHelper::merge(
                [
                    'id' => 'testapp',
                    'basePath' => __DIR__,
                    'vendorPath' => $this->getVendorPath(),
                    'aliases' => [
                        '@bower' => '@vendor/bower-asset',
                        '@npm' => '@vendor/npm-asset',
                    ],
                    'components' => [
                        'request' => [
                            'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                            'scriptFile' => __DIR__ . '/index.php',
                            'scriptUrl' => '/index.php',
                            'isConsoleRequest' => false,
                        ],
                    ],
                ],
                $config,
            )
        );
    }

    protected function getVendorPath()
    {
        $vendor = dirname(dirname(__DIR__)) . '/vendor';
        if (!is_dir($vendor)) {
            $vendor = dirname(dirname(dirname(dirname(__DIR__))));
        }

        return $vendor;
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE($expected, $actual, $message = '')
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserting two strings equality ignoring unicode whitespaces.
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsAnyWhitespace($expected, $actual, $message = ''){
        $expected = $this->sanitizeWhitespaces($expected);
        $actual = $this->sanitizeWhitespaces($actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two variables have the same type and value and sanitizes value if it is a string.
     * Used on objects, it asserts that two variables reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    protected function assertSameAnyWhitespace($expected, $actual, $message = ''){
        if (is_string($expected)) {
            $expected = $this->sanitizeWhitespaces($expected);
        }
        if (is_string($actual)) {
            $actual = $this->sanitizeWhitespaces($actual);
        }

        $this->assertSame($expected, $actual, $message);
    }

    /**
     * Asserts that a haystack contains a needle ignoring line endings.
     *
     * @param mixed $needle
     * @param mixed $haystack
     * @param string $message
     */
    protected function assertContainsWithoutLE($needle, $haystack, $message = '')
    {
        $needle = str_replace("\r\n", "\n", $needle);
        $haystack = str_replace("\r\n", "\n", $haystack);

        $this->assertStringContainsString($needle, $haystack, $message);
    }

    /**
     * Replaces unicode whitespaces with standard whitespace
     *
     * @see https://github.com/yiisoft/yii2/issues/19868 (ICU 72 changes)
     * @param $string
     * @return string
     */
    protected function sanitizeWhitespaces($string){
        return preg_replace("/[\pZ\pC]/u", " ", $string);
    }

    /**
     * Invokes a inaccessible method.
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     * @since 2.0.11
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new \ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);
        if ($revoke) {
            $method->setAccessible(false);
        }

        return $result;
    }

    /**
     * Sets an inaccessible object property to a designated value.
     * @param $object
     * @param $propertyName
     * @param $value
     * @param bool $revoke whether to make property inaccessible after setting
     * @since 2.0.11
     */
    protected function setInaccessibleProperty($object, $propertyName, $value, $revoke = true)
    {
        $class = new \ReflectionClass($object);
        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
        if ($revoke) {
            $property->setAccessible(false);
        }
    }

    /**
     * Gets an inaccessible object property.
     * @param $object
     * @param $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     * @return mixed
     */
    protected function getInaccessibleProperty($object, $propertyName, $revoke = true)
    {
        $class = new \ReflectionClass($object);
        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);
        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }


    /**
     * Asserts that value is one of expected values.
     *
     * @param mixed $actual
     * @param array $expected
     * @param string $message
     */
    public function assertIsOneOf($actual, array $expected, $message = '')
    {
        self::assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    /**
     * Changes db component config
     * @param $db
     */
    protected function switchDbConnection($db)
    {
        $databases = $this->getParam('databases');
        if (isset($databases[$db])) {
            $database = $databases[$db];
            Yii::$app->db->close();
            Yii::$app->db->dsn = isset($database['dsn']) ? $database['dsn'] : null;
            Yii::$app->db->username = isset($database['username']) ? $database['username'] : null;
            Yii::$app->db->password = isset($database['password']) ? $database['password'] : null;
        }
    }
}
