<?php

declare(strict_types=1);

namespace yiiunit\framework\filters;

use Yii;
use yii\base\Action;
use yii\filters\PageCache;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\View;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\CallbackDependency;
use yiiunit\TestCase;

/**
 * @group filters
 */
class PageCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['SCRIPT_FILENAME'] = '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
    }

    public static function cacheTestCaseProvider(): array
    {
        return [
            // Basic
            [
                [
                    'name' => 'disabled',
                    'properties' => [
                        'enabled' => false,
                    ],
                    'cacheable' => false,
                ],
            ],
            [
                [
                    'name' => 'simple',
                ],
            ],
            // Cookies
            [
                [
                    'name' => 'allCookies',
                    'properties' => [
                        'cacheCookies' => true,
                    ],
                    'cookies' => [
                        'test-cookie-1' => true,
                        'test-cookie-2' => true,
                    ],
                ],
            ],
            [
                [
                    'name' => 'someCookies',
                    'properties' => [
                        'cacheCookies' => ['test-cookie-2'],
                    ],
                    'cookies' => [
                        'test-cookie-1' => false,
                        'test-cookie-2' => true,
                    ],
                ],
            ],
            [
                [
                    'name' => 'noCookies',
                    'properties' => [
                        'cacheCookies' => false,
                    ],
                    'cookies' => [
                        'test-cookie-1' => false,
                        'test-cookie-2' => false,
                    ],
                ],
            ],
            // Headers
            [
                [
                    'name' => 'allHeaders',
                    'properties' => [
                        'cacheHeaders' => true,
                    ],
                    'headers' => [
                        'test-header-1' => true,
                        'test-header-2' => true,
                    ],
                ],
            ],
            [
                [
                    'name' => 'someHeaders',
                    'properties' => [
                        'cacheHeaders' => ['test-header-2'],
                    ],
                    'headers' => [
                        'test-header-1' => false,
                        'test-header-2' => true,
                    ],
                ],
            ],
            [
                [
                    'name' => 'noHeaders',
                    'properties' => [
                        'cacheHeaders' => false,
                    ],
                    'headers' => [
                        'test-header-1' => false,
                        'test-header-2' => false,
                    ],
                ],
            ],
            [
                [
                    'name' => 'originalNameHeaders',
                    'properties' => [
                        'cacheHeaders' => ['Test-Header-1'],
                    ],
                    'headers' => [
                        'Test-Header-1' => true,
                        'Test-Header-2' => false,
                    ],
                ],
            ],
            // All together
            [
                [
                    'name' => 'someCookiesSomeHeaders',
                    'properties' => [
                        'cacheCookies' => ['test-cookie-2'],
                        'cacheHeaders' => ['test-header-2'],
                    ],
                    'cookies' => [
                        'test-cookie-1' => false,
                        'test-cookie-2' => true,
                    ],
                    'headers' => [
                        'test-header-1' => false,
                        'test-header-2' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider cacheTestCaseProvider
     *
     * @param array $testCase
     */
    public function testCache(array $testCase): void
    {
        $cache = new Cache(new ArrayCache());
        $testCase = ArrayHelper::merge(['properties' => [], 'cacheable' => true], $testCase);

        if (isset(Yii::$app)) {
            $this->destroyApplication();
        }

        // Prepares the test response
        $this->mockWebApplication();

        $controller = new Controller('test', Yii::$app);
        $action = new Action('test', $controller);
        $filter = new PageCache(
            array_merge(
                [
                    'cache' => $cache,
                    'view' => new View(),
                ],
                $testCase['properties'],
            )
            );
        $this->assertTrue($filter->beforeAction($action), $testCase['name']);

        // Cookies
        $cookies = [];
        if (isset($testCase['cookies'])) {
            foreach (array_keys($testCase['cookies']) as $name) {
                $value = Yii::$app->security->generateRandomString();

                Yii::$app->response->cookies->add(
                    new Cookie(
                        [
                            'name' => $name,
                            'value' => $value,
                            'expire' => strtotime('now +1 year'),
                        ],
                    )
                );
                $cookies[$name] = $value;
            }
        }

        // Headers
        $headers = [];

        if (isset($testCase['headers'])) {
            foreach (array_keys($testCase['headers']) as $name) {
                $value = Yii::$app->security->generateRandomString();
                Yii::$app->response->headers->add($name, $value);

                $headers[$name] = $value;
            }
        }

        // Content
        $static = Yii::$app->security->generateRandomString();
        Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
        $content = $filter->view->render('@yiiunit/data/views/pageCacheLayout.php', ['static' => $static]);

        Yii::$app->response->content = $content;
        ob_start();
        Yii::$app->response->send();
        ob_end_clean();

        // Metadata
        $metadata = [
            'format' => Yii::$app->response->format,
            'version' => Yii::$app->response->version,
            'statusCode' => Yii::$app->response->statusCode,
            'statusText' => Yii::$app->response->statusText,
        ];

        if ($testCase['cacheable']) {
            $this->assertNotEmpty($this->getInaccessibleProperty($filter->cache->psr(), 'cache'), $testCase['name']);
        } else {
            $psr = $this->getInaccessibleProperty($filter->cache->psr(), 'handler');
            $this->assertEmpty($this->getInaccessibleProperty($psr, 'cache'), $testCase['name']);
            return;
        }

        // Verifies the cached response
        $this->destroyApplication();
        $this->mockWebApplication();
        $controller = new Controller('test', Yii::$app);
        $action = new Action('test', $controller);
        $filter = new PageCache(
            array_merge(
                [
                    'cache' => $cache,
                    'view' => new View(),
                ]
            ),
            $testCase['properties']
        );
        Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
        $this->assertFalse($filter->beforeAction($action), $testCase['name']);

        // Content
        $json = Json::decode(Yii::$app->response->content);
        $this->assertSame($static, $json['static'], $testCase['name']);
        $this->assertSame($dynamic, $json['dynamic'], $testCase['name']);

        // Metadata
        $this->assertSame($metadata['format'], Yii::$app->response->format, $testCase['name']);
        $this->assertSame($metadata['version'], Yii::$app->response->version, $testCase['name']);
        $this->assertSame($metadata['statusCode'], Yii::$app->response->statusCode, $testCase['name']);
        $this->assertSame($metadata['statusText'], Yii::$app->response->statusText, $testCase['name']);

        // Cookies
        if (isset($testCase['cookies'])) {
            foreach ($testCase['cookies'] as $name => $expected) {
                $this->assertSame($expected, Yii::$app->response->cookies->has($name), $testCase['name']);
                if ($expected) {
                    $this->assertSame($cookies[$name], Yii::$app->response->cookies->getValue($name), $testCase['name']);
                }
            }
        }

        // Headers
        if (isset($testCase['headers'])) {
            $headersExpected = Yii::$app->response->headers->toOriginalArray();
            foreach ($testCase['headers'] as $name => $expected) {
                $this->assertSame($expected, Yii::$app->response->headers->has($name), $testCase['name']);
                if ($expected) {
                    $this->assertSame($headers[$name], Yii::$app->response->headers->get($name), $testCase['name']);
                    $this->assertArrayHasKey($name, $headersExpected);
                }
            }
        }
    }

    public function testExpired(): void
    {
        $cache = new Cache(new ArrayCache());

        // Prepares the test response
        $this->mockWebApplication();

        $controller = new Controller('test', Yii::$app);
        $action = new Action('test', $controller);
        $filter = new PageCache(
            [
                'cache' => $cache,
                'view' => new View(),
                'duration' => 1,
            ],
        );

        $this->assertTrue($filter->beforeAction($action));

        $static = Yii::$app->security->generateRandomString();
        Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
        $content = $filter->view->render('@yiiunit/data/views/pageCacheLayout.php', ['static' => $static]);

        Yii::$app->response->content = $content;
        ob_start();
        Yii::$app->response->send();
        ob_end_clean();

        $psr = $this->getInaccessibleProperty($filter->cache->psr(), 'handler');
        $this->assertNotEmpty($this->getInaccessibleProperty($psr, 'cache'));

        // mock sleep(2);
        sleep(2);

        // Verifies the cached response
        $this->destroyApplication();
        $this->mockWebApplication();

        $controller = new Controller('test', Yii::$app);
        $action = new Action('test', $controller);
        $filter = new PageCache(
            [
                'cache' => $cache,
                'view' => new View(),
            ],
        );

        Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();

        $this->assertTrue($filter->beforeAction($action));
        ob_start();
        Yii::$app->response->send();
        ob_end_clean();
    }

    public function testVaryByRoute(): void
    {
        $testCases = [
            false,
            true,
        ];

        $cache = new Cache(new ArrayCache());

        foreach ($testCases as $enabled) {
            if (isset(Yii::$app)) {
                $this->destroyApplication();
            }

            // Prepares the test response
            $this->mockWebApplication();

            $controller = new Controller('test', Yii::$app);
            $action = new Action('test', $controller);
            Yii::$app->requestedRoute = $action->uniqueId;
            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                    'varyByRoute' => $enabled,
                ],
            );
            $this->assertTrue($filter->beforeAction($action));
            $static = Yii::$app->security->generateRandomString();
            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
            $content = $filter->view->render('@yiiunit/data/views/pageCacheLayout.php', ['static' => $static]);

            Yii::$app->response->content = $content;
            ob_start();
            Yii::$app->response->send();
            ob_end_clean();

            $psr = $this->getInaccessibleProperty($filter->cache->psr(), 'handler');
            $this->assertNotEmpty($this->getInaccessibleProperty($psr, 'cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();

            $controller = new Controller('test', Yii::$app);
            $action = new Action('test2', $controller);
            Yii::$app->requestedRoute = $action->uniqueId;
            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                    'varyByRoute' => $enabled,
                ],
            );

            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
            $this->assertSame($enabled, $filter->beforeAction($action));

            ob_start();
            Yii::$app->response->send();
            ob_end_clean();
        }
    }

    public function testVariations(): void
    {
        $testCases = [
            [true, 'name' => 'value'],
            [false, 'name' => 'value2'],
        ];

        foreach ($testCases as $testCase) {
            $cache = new Cache(new ArrayCache());

            if (isset(Yii::$app)) {
                $this->destroyApplication();
            }

            $expected = array_shift($testCase);

            // Prepares the test response
            $this->mockWebApplication();

            $controller = new Controller('test', Yii::$app);
            $action = new Action('test', $controller);
            $originalVariations = $testCases[0];

            array_shift($originalVariations);

            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                    'variations' => $originalVariations,
                ],
            );
            $this->assertTrue($filter->beforeAction($action));
            $static = Yii::$app->security->generateRandomString();
            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
            $content = $filter->view->render('@yiiunit/data/views/pageCacheLayout.php', ['static' => $static]);

            Yii::$app->response->content = $content;
            ob_start();
            Yii::$app->response->send();
            ob_end_clean();

            $psr = $this->getInaccessibleProperty($filter->cache->psr(), 'handler');
            $this->assertNotEmpty($this->getInaccessibleProperty($psr, 'cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();

            $controller = new Controller('test', Yii::$app);
            $action = new Action('test', $controller);
            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                    'variations' => $testCase,
                ],
            );
            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
            $this->assertNotSame($expected, $filter->beforeAction($action));

            ob_start();
            Yii::$app->response->send();
            ob_end_clean();
        }
    }

    public function testDependency(): void
    {
        $testCases = [
            false,
            true,
        ];

        $cache = new Cache(new ArrayCache());

        foreach ($testCases as $changed) {
            if (isset(Yii::$app)) {
                $this->destroyApplication();
            }

            // Prepares the test response
            $this->mockWebApplication();
            $controller = new Controller('test', Yii::$app);
            $action = new Action('test', $controller);
            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                    'dependency' => new CallbackDependency(
                        static function () {
                            return Yii::$app->params['dependency'] ?? [];
                        },
                    ),
                ],
            );
            $this->assertTrue($filter->beforeAction($action));
            $static = Yii::$app->security->generateRandomString();
            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();
            Yii::$app->params['dependency'] = $dependency = Yii::$app->security->generateRandomString();
            $content = $filter->view->render('@yiiunit/data/views/pageCacheLayout.php', ['static' => $static]);

            Yii::$app->response->content = $content;
            ob_start();
            Yii::$app->response->send();
            ob_end_clean();

            $psr = $this->getInaccessibleProperty($filter->cache->psr(), 'handler');
            $this->assertNotEmpty($this->getInaccessibleProperty($psr, 'cache'));

            // Verifies the cached response
            $this->destroyApplication();
            $this->mockWebApplication();
            
            $controller = new Controller('test', Yii::$app);
            $action = new Action('test', $controller);
            $filter = new PageCache(
                [
                    'cache' => $cache,
                    'view' => new View(),
                ],
            );

            Yii::$app->params['dynamic'] = $dynamic = Yii::$app->security->generateRandomString();

            if ($changed) {
                Yii::$app->params['dependency'] = Yii::$app->security->generateRandomString();
            } else {
                Yii::$app->params['dependency'] = $dependency;
            }

            $this->assertSame($changed, $filter->beforeAction($action));

            ob_start();
            Yii::$app->response->send();
            ob_end_clean();
        }
    }

    public function testCalculateCacheKey(): void
    {
        $expected = ['yii\filters\PageCache', 'test', 'ru'];
        Yii::$app->requestedRoute = 'test';

        $keys = $this->invokeMethod(new PageCache(['variations' => ['ru']]), 'calculateCacheKey');
        $this->assertSame($expected, $keys);

        $keys = $this->invokeMethod(new PageCache(['variations' => 'ru']), 'calculateCacheKey');
        $this->assertSame($expected, $keys);

        $expected = ['yii\filters\PageCache', 'test'];
        $keys = $this->invokeMethod(new PageCache(), 'calculateCacheKey');
        $this->assertsame($expected, $keys);
    }

    public function testClosureVariations(): void
    {
        $expected = ['yii\filters\PageCache', 'test', 'foobar'];
        $keys = $this->invokeMethod(
            new PageCache(
                [
                    'variations' => static function(): array {
                        return [
                            'foobar'
                        ];
                    }
                ]
            ),
            'calculateCacheKey',
        );
        $this->assertSame($expected, $keys);

        // test type cast of string
        $expected = ['yii\filters\PageCache', 'test', 'foobarstring'];
        $keys = $this->invokeMethod(
            new PageCache(
                [
                    'variations' => static fn(): string => 'foobarstring',
                ],
            ),
            'calculateCacheKey',
        );
        $this->assertSame($expected, $keys);
    }
}
