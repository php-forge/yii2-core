<?php

declare(strict_types=1);

namespace yii\web;

use PDO;
use SessionHandlerInterface;
use Yii;
use yii\db\PdoValue;
use yii\db\Query;

class DbSessionHandler implements SessionHandlerInterface
{
    public function __construct(private DbSession $session)
    {
    }

    public function open(string $savePath, string $sessionName): bool
    {
        if ($this->session->getUseStrictMode()) {
            $id = $this->session->getId();

            if (!$this->getReadQuery($id)->exists($this->session->db)) {
                //This session id does not exist, mark it for forced regeneration
                $this->session->_forceRegenerateId = $id;
            }
        }

        return true;
    }

    public function close(): bool
    {
        if ($this->session->getIsActive()) {
            // prepare writeCallback fields before session closes
            $this->session->fields = $this->session->composeFields();
            YII_DEBUG ? session_write_close() : @session_write_close();
        }

        return true;
    }

    public function read(string $id): string
    {
        $query = $this->getReadQuery($id);

        if ($this->session->readCallback !== null) {
            $fields = $query->one($this->session->db);
            return $fields === false ? '' : $this->session->extractData($fields);
        }

        $data = $query->select(['data'])->scalar($this->session->db);

        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        if ($this->session->getUseStrictMode() && $id === $this->session->_forceRegenerateId) {
            //Ignore write when forceRegenerate is active for this id
            return true;
        }

        // exception must be caught in session write handler
        // https://www.php.net/manual/en/function.session-set-save-handler.php#refsect1-function.session-set-save-handler-notes
        try {
            // ensure backwards compatability (fixed #9438)
            if ($this->session->writeCallback && !$this->session->fields) {
                $this->session->fields = $this->session->composeFields();
            }
            // ensure data consistency
            if (!isset($this->session->fields['data'])) {
                $this->session->fields['data'] = $data;
            } else {
                $_SESSION = $this->session->fields['data'];
            }

            // ensure 'id' and 'expire' are never affected by [[writeCallback]]
            $this->session->fields = array_merge(
                $this->session->fields,
                [
                    'id' => $id,
                    'expire' => time() + $this->session->getTimeout(),
                ],
            );
            $this->session->fields = $this->typecastFields($this->session->fields);
            $this->session->db->createCommand()->upsert($this->session->sessionTable, $this->session->fields)->execute();
            $this->session->fields = [];
        } catch (\Exception $e) {
            Yii::$app->errorHandler->handleException($e);
            return false;
        }

        return true;
    }

    public function destroy(string $id): bool
    {
        $this->session->db->createCommand()->delete($this->session->sessionTable, ['id' => $id])->execute();

        return true;
    }

    public function gc(int $maxLifetime): false|int
    {
        $this->session->db->createCommand()
            ->delete($this->session->sessionTable, '[[expire]]<:expire', [':expire' => time()])
            ->execute();

        return 0;
    }

    /**
     * Generates a query to get the session from db.
     *
     * @param string $id The id of the session.
     *
     * @return Query The query to get the session from db.
     */
    private function getReadQuery(string $id): Query
    {
        return (new Query())
            ->from($this->session->sessionTable)
            ->where('[[expire]]>:expire AND [[id]]=:id', [':expire' => time(), ':id' => $id]);
    }

    /**
     * Method typecasts $fields before passing them to PDO.
     * Default implementation casts field `data` to `\PDO::PARAM_LOB`.
     * You can override this method in case you need special type casting.
     *
     * @param array $fields Fields, that will be passed to PDO. Key - name, Value - value.
     *
     * @return array Typecasted fields.
     */
    private function typecastFields(array $fields): array
    {
        if (isset($fields['data']) && !is_array($fields['data']) && !is_object($fields['data'])) {
            $fields['data'] = new PdoValue($fields['data'], PDO::PARAM_LOB);
        }

        return $fields;
    }
}
