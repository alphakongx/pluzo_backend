<?php

use yii\db\Migration;

/**
 * Class m201109_175641_create_last_seen
 */
class m201109_175641_create_last_seen extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%last_seen}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'chat' => $this->integer(),
            'time' => $this->string(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%last_seen}}');
    }
}
