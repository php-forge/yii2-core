<?php

declare(strict_types=1);

namespace yii\web\session\handler;

use yii\db\Connection;
use yii\db\Query;
use yii\web\session\SessionHandlerInterface;

class DbSessionHandler implements SessionHandlerInterface
{
    private string $forceRegenerateId = '';
    private int|null $timeout = 0;
    private bool $useStrictMode = false;

    public function __construct(private Connection $db, private string $sessionTable = '{{%session}}')
    {
        $this->timeout = (int) ini_get('session.gc_maxlifetime');
        $this->useStrictMode = (bool) ini_get('session.use_strict_mode');
    }

    public function open(string $savePath, string $sessionName): bool
    {
        if ($this->useStrictMode) {
            $id = session_id();

            if (!$this->getReadQuery($id)->exists($this->db)) {
                $this->forceRegenerateId = $id;
            }
        }

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id,  mixed $defaultValue = ''): string
    {
        $query = $this->getReadQuery($id);
        $data = $query->select(['data'])->scalar($this->db);

        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        $this->db->createCommand()
            ->upsert(
                $this->sessionTable,
                ['id' => $id, 'expire' => time() + $this->timeout, 'data' => $data],
            )->execute();

        return true;
    }

    public function destroy(string $id): bool
    {
        $this->db->createCommand()->delete($this->sessionTable, ['id' => $id])->execute();

        return true;
    }

    public function gc(int $maxLifetime): int|false
    {
        return $this->db->createCommand()
            ->delete($this->sessionTable, '[[expire]]<:expire', [':expire' => time()])
            ->execute();
    }

    public function isRegenerateId(): bool
    {
        return $this->forceRegenerateId !== '';
    }

    /**
     * Generates a query to get the session from db.
     *
     * @param string $id The id of the session.
     *
     * @return Query The query to get the session from db.
     */
    private function getReadQuery($id)
    {
        return (new Query())
            ->from($this->sessionTable)
            ->where('[[expire]]>:expire AND [[id]]=:id', [':expire' => time(), ':id' => $id]);
    }
}
