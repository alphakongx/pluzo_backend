<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tempcode}}`.
 */
class m201028_091908_create_tempcode_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tempcode}}', [
            'id' => $this->primaryKey(),            
            'user_id' => $this->integer(),
            'expires_at' => $this->string()->Null(),
            'type' => $this->string(),
            'code' => $this->string(),
            'data' => $this->string()->Null(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tempcode}}');
    }
}
