<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%translator_booking}}`.
 */
class m260130_122132_create_translator_booking_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%translator_booking}}', [
            'id' => $this->primaryKey(),
            'translator_id' => $this->integer()->notNull(),
            'date' => $this->date()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_booking_translator',
            '{{%translator_booking}}',
            'translator_id',
            '{{%translator}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->createIndex(
            'uidx_booking_translator_date',
            '{{%translator_booking}}',
            ['translator_id', 'date'],
            true
        );

        $this->createIndex('idx_booking_date', '{{%translator_booking}}', 'date');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_booking_translator', '{{%translator_booking}}');
        $this->dropTable('{{%translator_booking}}');
    }
}
