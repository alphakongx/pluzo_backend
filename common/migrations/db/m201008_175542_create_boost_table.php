<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%boost}}`.
 */
class m201008_175542_create_boost_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%advance}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'created_at' => $this->string(),
            'used_time' => $this->string()->Null(),
            'expires_at' => $this->string()->Null(),
            'payment_id' => $this->string()->Null(),
            'status' => $this->integer(),
            'type' => $this->integer(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%advance}}');
    }
}
