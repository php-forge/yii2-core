<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n;

use Yii;
use yii\base\Event;
use yii\i18n\I18N;
use yii\i18n\PhpMessageSource;
use yiiunit\TestCase;

/**
 * @group i18n
 */
class I18NTest extends TestCase
{
    public I18n $i18n;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();
        $this->setI18N();
    }

    protected function setI18N(): void
    {
        $this->i18n = new I18N(
            [
                'translations' => [
                    'test' => [
                        'class' => $this->getMessageSourceClass(),
                        'basePath' => '@yiiunit/data/i18n/messages',
                    ],
                ],
            ],
        );
    }

    public function testTranslate(): void
    {
        $msg = 'The dog runs fast.';

        // source = target. Should be returned as is.
        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', $msg, [], 'en-US'));

        // exact match
        $this->assertSame('Der Hund rennt schnell.', $this->i18n->translate('test', $msg, [], 'de-DE'));

        // fallback to just language code with absent exact match
        $this->assertSame('Собака бегает быстро.', $this->i18n->translate('test', $msg, [], 'ru-RU'));

        // fallback to just langauge code with present exact match
        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));
    }

    public function testDefaultSource(): void
    {
        $i18n = new I18N(
            [
                'translations' => [
                    '*' => [
                        'class' => $this->getMessageSourceClass(),
                        'basePath' => '@yiiunit/data/i18n/messages',
                        'fileMap' => [
                            'test' => 'test.php',
                            'foo' => 'test.php',
                        ],
                    ],
                ],
            ],
        );

        $msg = 'The dog runs fast.';

        // source = target. Should be returned as is.
        $this->assertSame($msg, $i18n->translate('test', $msg, [], 'en-US'));

        // exact match
        $this->assertSame('Der Hund rennt schnell.', $i18n->translate('test', $msg, [], 'de-DE'));
        $this->assertSame('Der Hund rennt schnell.', $i18n->translate('foo', $msg, [], 'de-DE'));
        $this->assertSame($msg, $i18n->translate('bar', $msg, [], 'de-DE'));

        // fallback to just language code with absent exact match
        $this->assertSame('Собака бегает быстро.', $i18n->translate('test', $msg, [], 'ru-RU'));

        // fallback to just langauge code with present exact match
        $this->assertSame('Hallo Welt!', $i18n->translate('test', 'Hello world!', [], 'de-DE'));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/7964
     */
    public function testSourceLanguageFallback(): void
    {
        $i18n = new I18N(
            [
                'translations' => [
                    '*' => new PhpMessageSource([
                            'basePath' => '@yiiunit/data/i18n/messages',
                            'sourceLanguage' => 'de-DE',
                            'fileMap' => [
                                'test' => 'test.php',
                                'foo' => 'test.php',
                            ],
                        ]
                    ),
                ],
            ],
        );

        $msg = 'The dog runs fast.';

        // source = target. Should be returned as is.
        $this->assertSame($msg, $i18n->translate('test', $msg, [], 'de-DE'));

        // target is less specific, than a source. Messages from sourceLanguage file should be loaded as a fallback
        $this->assertSame('Der Hund rennt schnell.', $i18n->translate('test', $msg, [], 'de'));
        $this->assertSame('Hallo Welt!', $i18n->translate('test', 'Hello world!', [], 'de'));

        // target is a different language than source
        $this->assertSame('Собака бегает быстро.', $i18n->translate('test', $msg, [], 'ru-RU'));
        $this->assertSame('Собака бегает быстро.', $i18n->translate('test', $msg, [], 'ru'));
        $this->assertSame('The dog runs fast (en-150).', $i18n->translate('test', $msg, [], 'en-150'));
        $this->assertSame('The dog runs fast.', $i18n->translate('test', $msg, [], 'en'));

        // language is set to null
        $this->assertSame($msg, $i18n->translate('test', $msg, [], null));
    }

    public function testTranslateParams(): void
    {
        $msg = 'His speed is about {n} km/h.';
        $params = ['n' => 42];

        $this->assertSame('His speed is about 42 km/h.', $this->i18n->translate('test', $msg, $params, 'en-US'));
        $this->assertSame(
            'Seine Geschwindigkeit beträgt 42 km/h.',
            $this->i18n->translate('test', $msg, $params, 'de-DE'),
        );
    }

    public function testTranslateParams2(): void
    {
        $msg = 'His name is {name} and his speed is about {n, number} km/h.';
        $params = [
            'n' => 42,
            'name' => 'DA VINCI', // https://petrix.com/dognames/d.html
        ];

        $this->assertSame(
            'His name is DA VINCI and his speed is about 42 km/h.',
            $this->i18n->translate('test', $msg, $params, 'en-US'),
        );
        $this->assertSame(
            'Er heißt DA VINCI und ist 42 km/h schnell.',
            $this->i18n->translate('test', $msg, $params, 'de-DE'),
        );
    }

    public function testSpecialParams(): void
    {
        $msg = 'His speed is about {0} km/h.';

        $this->assertSame('His speed is about 0 km/h.', $this->i18n->translate('test', $msg, 0, 'en-US'));
        $this->assertSame('His speed is about 42 km/h.', $this->i18n->translate('test', $msg, 42, 'en-US'));
        $this->assertSame('His speed is about {0} km/h.', $this->i18n->translate('test', $msg, null, 'en-US'));
        $this->assertSame('His speed is about {0} km/h.', $this->i18n->translate('test', $msg, [], 'en-US'));
    }

    /**
     * When translation is missing source language should be used for formatting.
     *
     * @see https://github.com/yiisoft/yii2/issues/2209
     */
    public function testMissingTranslationFormatting(): void
    {
        $this->assertSame(
            '1 item',
            $this->i18n->translate('test', '{0, number} {0, plural, one{item} other{items}}', [1], 'hu'),
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/7093
     */
    public function testRussianPlurals(): void
    {
        $this->assertSame(
            'На диване лежит 6 кошек!',
            $this->i18n->translate(
                'test',
                'There {n, plural, =0{no cats} =1{one cat} other{are # cats}} on lying on the sofa!',
                ['n' => 6],
                'ru',
            ),
        );
    }

    public function testUsingSourceLanguageForMissingTranslation(): void
    {
        Yii::$app->sourceLanguage = 'ru';
        Yii::$app->language = 'en';

        $msg = '{n, plural, =0{Нет комментариев} =1{# комментарий} one{# комментарий} few{# комментария} many{# комментариев} other{# комментария}}';

        $this->assertSame('5 комментариев', Yii::t('app', $msg, ['n' => 5]));
        $this->assertSame('3 комментария', Yii::t('app', $msg, ['n' => 3]));
        $this->assertSame('1 комментарий', Yii::t('app', $msg, ['n' => 1]));
        $this->assertSame('21 комментарий', Yii::t('app', $msg, ['n' => 21]));
        $this->assertSame('Нет комментариев', Yii::t('app', $msg, ['n' => 0]));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/2519
     */
    public function testMissingTranslationEvent(): void
    {
        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));
        $this->assertSame(
            'Missing translation message.',
            $this->i18n->translate('test', 'Missing translation message.', [], 'de-DE'),
        );
        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));

        Event::on(PhpMessageSource::class, PhpMessageSource::EVENT_MISSING_TRANSLATION, function ($event) {});

        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));
        $this->assertSame(
            'Missing translation message.',
            $this->i18n->translate('test', 'Missing translation message.', [], 'de-DE'),
        );
        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));

        Event::off(PhpMessageSource::class, PhpMessageSource::EVENT_MISSING_TRANSLATION);
        Event::on(
            PhpMessageSource::class,
            PhpMessageSource::EVENT_MISSING_TRANSLATION,
            static function (Event $event): void {
                if ($event->message == 'New missing translation message.') {
                    $event->translatedMessage = 'TRANSLATION MISSING HERE!';
                }
            }
        );

        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));
        $this->assertSame(
            'Another missing translation message.',
            $this->i18n->translate('test', 'Another missing translation message.', [], 'de-DE'),
        );
        $this->assertSame(
            'Missing translation message.',
            $this->i18n->translate('test', 'Missing translation message.', [], 'de-DE'),
        );
        $this->assertSame(
            'TRANSLATION MISSING HERE!',
            $this->i18n->translate('test', 'New missing translation message.', [], 'de-DE'),
        );
        $this->assertSame('Hallo Welt!', $this->i18n->translate('test', 'Hello world!', [], 'de-DE'));

        Event::off(PhpMessageSource::class, PhpMessageSource::EVENT_MISSING_TRANSLATION);
    }

    /**
     * @dataProvider \yiiunit\framework\i18n\provider\I18NProvider::sourceLanguageDataProvider
     */
    public function testIssue11429(string $sourceLanguage): void
    {
        Yii::$app->sourceLanguage = $sourceLanguage;

        $logger = Yii::getLogger();
        $logger->messages = [];
        $filter = function (array $array): bool {
            // Ensures that error message is related to PhpMessageSource
            $className = $this->getMessageSourceClass();

            return substr_compare($array[2], $className, 0, strlen($className)) === 0;
        };

        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', 'The dog runs fast.', [], 'en-GB'));
        $this->assertSame([], array_filter($logger->messages, $filter));

        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', 'The dog runs fast.', [], 'en'));
        $this->assertSame([], array_filter($logger->messages, $filter));

        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', 'The dog runs fast.', [], 'en-CA'));
        $this->assertSame([], array_filter($logger->messages, $filter));

        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', 'The dog runs fast.', [], 'hz-HZ'));
        $this->assertCount(1, array_filter($logger->messages, $filter));
        $logger->messages = [];

        $this->assertSame('The dog runs fast.', $this->i18n->translate('test', 'The dog runs fast.', [], 'hz'));
        $this->assertCount(1, array_filter($logger->messages, $filter));
        $logger->messages = [];
    }

    /**
     * Formatting a message that contains params but they are not provided.
     * @see https://github.com/yiisoft/yii2/issues/10884
     */
    public function testFormatMessageWithNoParam(): void
    {
        $message = 'Incorrect password (length must be from {min, number} to {max, number} symbols).';

        $this->assertSame($message, $this->i18n->format($message, ['attribute' => 'password'], 'en'));
    }

    public function testFormatMessageWithDottedParameters(): void
    {
        $message = 'date: {dt.test}';

        $this->assertSame('date: 1510147434', $this->i18n->format($message, ['dt.test' => 1510147434], 'en'));

        $message = 'date: {dt.test,date}';
        
        $this->assertSame('date: Nov 8, 2017', $this->i18n->format($message, ['dt.test' => 1510147434], 'en'));
    }

    private function getMessageSourceClass(): string
    {
        return PhpMessageSource::class;
    }
}
