<?php

use yii\db\Migration;

/**
 * Class m200816_123726_create_table_badges
 */
class m200816_123726_create_table_badges extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%badge}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'badge_id' => $this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%badge}}');
    }

}
