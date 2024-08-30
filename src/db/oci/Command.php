<?php

declare(strict_types=1);

namespace yii\db\oci;

use PDO;

/**
 * Command represents an Oracle SQL statement to be executed against a database.
 *
 * {@inheritdoc}
 */
class Command extends \yii\db\Command
{
    /**
     * {@inheritdoc}
     */
    protected function bindPendingParams(): void
    {
        $paramsPassedByReference = [];

        foreach ($this->pendingParams as $name => $value) {
            if (\PDO::PARAM_STR === $value[1]) {
                $paramsPassedByReference[$name] = $value[0];

                $this->pdoStatement->bindParam(
                    $name,
                    $paramsPassedByReference[$name],
                    $value[1],
                    strlen((string) $value[0])
                );
            } else {
                $this->pdoStatement->bindValue($name, $value[0], $value[1]);
            }
        }

        $this->pendingParams = [];
    }

    public function insertWithReturningPks(string $table, array $columns): array|bool|int
    {
        $params = [];
        $returnParams = [];

        $sql = $this->db->getQueryBuilder()->insertWithReturningPks($table, $columns, $params, $returnParams);

        $this->setSql($sql)->bindValues($params)->prepare(false);

        if (str_contains($sql, ' RETURNING ') === false) {
            return $this->execute();
        }

        foreach ($returnParams as $name => &$value) {
            $this->bindParam($name, $value['value'], $value['dataType'], $value['size']);
        }

        if (!$this->execute()) {
            return false;
        }

        foreach ($returnParams as $returnParam) {
            if (
                $returnParam['dataType'] === PDO::PARAM_STR &&
                isset($returnParam['value']) &&
                preg_match('/\s$/', $returnParam['value']) === 1
            ) {
                $returnParam['value'] = rtrim($returnParam['value']);
            } else {
                $result[$returnParam['column']] = $returnParam['value'];
            }
        }

        unset($value);

        return $result;
    }
}
