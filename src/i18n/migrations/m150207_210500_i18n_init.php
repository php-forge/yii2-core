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

        $sourceMessageSchema = $this->sourceMessageSchema();
        $messageSchema = $this->messageSchema();

        if ($this->db->driverName === 'sqlite') {
            $sourceMessageSchema = $this->sourceMessageSchemaSqlite();
            $messageSchema = $this->messageSchemaSqlite();
        }

        $this->createTable('{{%source_message}}', $sourceMessageSchema, $tableOptions);
        $this->createTable('{{%message}}', $messageSchema, $tableOptions);

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
        if ($this->db->driverName !== 'mysql' && $this->db->driverName !== 'sqlite') {
            $this->dropPrimaryKey('pk_message_id_language', '{{%message}}');
        }

        $this->dropTable('{{%message}}');
        $this->dropTable('{{%source_message}}');
    }

    private function messageSchema(): array
    {
        return [
            'id' => $this->integer()->notNull(),
            'language' => $this->string(16)->notNull(),
            'translation' => $this->text(),
        ];
    }

    private function messageSchemaSqlite(): array
    {
        return [
            'id' => $this->integer()->notNull(),
            'language' => $this->string(16)->notNull(),
            'translation' => $this->text(),
            "CONSTRAINT [[PK_message_id_language]] PRIMARY KEY ([[id]], [[language]])",
            "CONSTRAINT [[FK_message_source_message]] FOREIGN KEY ([[id]]) REFERENCES [[source_message]] ([[id]]) ON DELETE CASCADE ON UPDATE RESTRICT",
        ];
    }

    private function sourceMessageSchema(): array
    {
        return [
            'id' => $this->primaryKey(),
            'category' => $this->string(),
            'message' => $this->text(),
        ];
    }

    private function sourceMessageSchemaSqlite(): array
    {
        return [
            'id' => $this->integer()->notNull(),
            'category' => $this->string(),
            'message' => $this->text(),
            "CONSTRAINT [[PK_source_message_id]] PRIMARY KEY ([[id]])",
        ];
    }
}
