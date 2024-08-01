<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Initializes i18n messages tables.
 */
class m150207_210500_i18n_init extends Migration
{
    public function up(): void
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            '{{%source_message}}',
            [
                'id' => $this->primaryKey(),
                'category' => $this->string(),
                'message' => $this->text(),
            ],
            $tableOptions,
        );

        $this->createTable(
            '{{%message}}',
            [
                'id' => $this->integer()->notNull(),
                'language' => $this->string(16)->notNull(),
                'translation' => $this->text(),
            ],
            $tableOptions,
        );

        $onUpdateConstraint = 'RESTRICT';

        if ($this->db->driverName === 'sqlsrv') {
            // 'NO ACTION' is equivalent to 'RESTRICT' in MSSQL
            $onUpdateConstraint = 'NO ACTION';
        }

        if ($this->db->driverName !== 'sqlite') {
            $this->addPrimaryKey('pk_message_id_language', '{{%message}}', ['id', 'language']);
            $this->addForeignKey(
                'fk_message_source_message',
                '{{%message}}',
                'id',
                '{{%source_message}}',
                'id',
                'CASCADE',
                $onUpdateConstraint,
            );
        }

        $this->createIndex('idx_source_message_category', '{{%source_message}}', 'category');
        $this->createIndex('idx_message_language', '{{%message}}', 'language');
    }

    public function down(): void
    {
        $version = $this->db->getServerVersion();

        if (
            $this->db->driverName !== 'sqlite' &&
            \version_compare($version, '5.7', '<') && \stripos($version, 'MariaDb') === false
        ) {
            $this->dropPrimaryKey('pk_message_id_language', '{{%message}}');
        }

        $this->dropTable('{{%message}}');
        $this->dropTable('{{%source_message}}');
    }
}
