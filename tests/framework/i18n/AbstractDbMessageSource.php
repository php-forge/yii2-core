<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n;

use Yii;
use yii\base\Event;
use yii\db\Connection;
use yii\i18n\DbMessageSource;
use yii\i18n\I18N;
use yiiunit\framework\console\controllers\EchoMigrateController;
use yiiunit\framework\i18n\I18NTest;

/**
 * @group db
 * @group i18n
 */
abstract class AbstractDbMessageSource extends I18NTest
{
    protected Connection|null $db = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateUp();
    }

    protected function tearDown(): void
    {
        $this->migrateDown();

        $this->db->close();
        $this->db = null;

        parent::tearDown();
    }

    public function testMissingTranslationEvent(): void
    {
        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );
        $this->assertSame(
            'Missing translation message.',
            $this->i18n->translate('test', 'Missing translation message.', [], 'de-DE'),
        );
        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );

        Event::on(DbMessageSource::class, DbMessageSource::EVENT_MISSING_TRANSLATION, function (Event $event) {});

        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );
        $this->assertSame(
            'Missing translation message.',
            $this->i18n->translate('test', 'Missing translation message.', [], 'de-DE'),
        );
        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );

        Event::off(DbMessageSource::class, DbMessageSource::EVENT_MISSING_TRANSLATION);
        Event::on(
            DbMessageSource::class,
            DbMessageSource::EVENT_MISSING_TRANSLATION,
            static function (Event $event): void {
                if ($event->message == 'New missing translation message.') {
                    $event->translatedMessage = 'TRANSLATION MISSING HERE!';
                }
            }
        );

        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );
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
        $this->assertSame(
            'Hallo Welt!',
            $this->i18n->translate('test', 'Hello world!', [], 'de-DE'),
        );

        Event::off(DbMessageSource::class, DbMessageSource::EVENT_MISSING_TRANSLATION);
    }


    public function testIssue11429($sourceLanguage = null): void
    {
        $this->markTestSkipped('DbMessageSource does not produce any errors when messages file is missing.');
    }

    protected function setI18N(): void
    {
        $this->i18n = new I18N(
            [
                'translations' => [
                    'test' => [
                        'class' => DbMessageSource::class,
                        'db' => $this->db,
                    ],
                ],
            ]
        );
    }

    protected function migrateUp(): void
    {
        $this->runConsoleAction('migrate/up', ['migrationPath' => '@yii/i18n/migrations/', 'interactive' => false]);

        $this->db->createCommand()->truncateTable('source_message');

        $this->db->createCommand()->batchInsert(
            'source_message',
            ['category', 'message'],
            [
                ['test', 'Hello world!'], // id = 1
                ['test', 'The dog runs fast.'], // id = 2
                ['test', 'His speed is about {n} km/h.'], // id = 3
                ['test', 'His name is {name} and his speed is about {n, number} km/h.'], // id = 4
                ['test', 'There {n, plural, =0{no cats} =1{one cat} other{are # cats}} on lying on the sofa!'], // id = 5
            ],
        )->execute();

        $this->db->createCommand()->insert(
            'message',
            ['id' => 1, 'language' => 'de', 'translation' => 'Hallo Welt!'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            ['id' => 2, 'language' => 'de-DE', 'translation' => 'Der Hund rennt schnell.'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            ['id' => 2, 'language' => 'en-US', 'translation' => 'The dog runs fast (en-US).'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            ['id' => 2, 'language' => 'ru', 'translation' => 'Собака бегает быстро.'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            ['id' => 3, 'language' => 'de-DE', 'translation' => 'Seine Geschwindigkeit beträgt {n} km/h.'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            ['id' => 4, 'language' => 'de-DE', 'translation' => 'Er heißt {name} und ist {n, number} km/h schnell.'],
        )->execute();
        $this->db->createCommand()->insert(
            'message',
            [
                'id' => 5,
                'language' => 'ru',
                'translation' => 'На диване {n, plural, =0{нет кошек} =1{лежит одна кошка} one{лежит # кошка} few{лежит # кошки} many{лежит # кошек} other{лежит # кошки}}!',
            ],
        )->execute();
    }

    protected function migrateDown(): void
    {
        $this->runConsoleAction('migrate/down', ['migrationPath' => '@yii/i18n/migrations/', 'interactive' => false]);
    }

    protected function runConsoleAction(string $route, array $params = []): void
    {
        if (Yii::$app === null) {
            new \yii\console\Application(
                [
                    'id' => 'Migrator',
                    'basePath' => '@yiiunit',
                    'controllerMap' => [
                        'migrate' => EchoMigrateController::class,
                    ],
                    'components' => [
                        'db' => $this->db,
                    ],
                ],
            );
        }

        ob_start();

        $result = Yii::$app->runAction($route, $params);

        echo 'Result is ' . $result;

        if ($result !== \yii\console\ExitCode::OK) {
            ob_end_flush();
        } else {
            ob_end_clean();
        }
    }
}
