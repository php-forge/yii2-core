<?php

declare(strict_types=1);

namespace yii\web\session;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;
use yii\web\session\handler\DbSessionHandler;

/**
 * DbSession extends [[Session]] by using database as session data storage.
 *
 * By default, DbSession stores session data in a DB table named 'session'. This table must be pre-created. The table
 * name can be changed by setting [[sessionTable]].
 *
 * The following example shows how you can configure the application to use DbSession:
 * Add the following to your application config under `components`:
 *
 * ```php
 * 'session' => [
 *     'class' => 'yii\web\session\DbSession',
 *     // 'db' => 'mydb',
 *     // 'sessionTable' => 'my_session',
 * ]
 * ```
 */
class DbSession extends Session
{
    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the DbSession object is created, if you want to change this property, you should only assign it with a DB
     * connection object.
     */
    public Connection|array|string|null $db = 'db';
    /**
     * @var string the name of the DB table that stores the session data.
     * The table should be pre-created as follows:
     *
     * ```sql
     * CREATE TABLE session
     * (
     *     id CHAR(40) NOT NULL PRIMARY KEY,
     *     expire INTEGER,
     *     data BLOB
     * )
     * ```
     *
     * where 'BLOB' refers to the BLOB-type of your preferred DBMS. Below are the BLOB type that can be used for some
     * popular DBMS:
     *
     * - MySQL: LONGBLOB
     * - PostgreSQL: BYTEA
     * - MSSQL: BLOB
     *
     * When using DbSession in a production server, we recommend you create a DB index for the 'expire' column in the
     * session table to improve the performance.
     *
     * Note that according to the php.ini setting of `session.hash_function`, you may need to adjust the length of the
     * `id` column. For example, if `session.hash_function=sha256`, you should use length 64 instead of 40.
     */
    public string $sessionTable = '{{%session}}';

    /**
     * Initializes the DbSession component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DB connection.
     *
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->_handler ??= Instance::ensure(
            [
                'class' => DbSessionHandler::class,
                '__construct()' => [$this->db, $this->sessionTable],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->getIsActive()) {
            YII_DEBUG ? session_write_close() : @session_write_close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function regenerateID(bool $deleteOldSession = false): void
    {
        $oldID = session_id();

        // if no session is started, there is nothing to regenerate
        if (empty($oldID)) {
            return;
        }

        parent::regenerateID($deleteOldSession);

        $newID = session_id();

        // if session id regeneration failed, no need to create/update it.
        if (empty($newID)) {
            Yii::warning('Failed to generate new session ID', __METHOD__);

            return;
        }

        $row = $this->db->useMaster(function () use ($oldID) {
            return (new Query())->from($this->sessionTable)
               ->where(['id' => $oldID])
               ->createCommand($this->db)
               ->queryOne();
        });

        if ($row !== false && $this->getIsActive()) {
            if ($deleteOldSession) {
                $this->db->createCommand()->update($this->sessionTable, ['id' => $newID], ['id' => $oldID])->execute();
            } else {
                $row['id'] = $newID;
                $this->db->createCommand()->insert($this->sessionTable, $row)->execute();
            }
        }
    }
}
