<?php

use yii\db\Migration;

/**
 * Class m201123_180533_create_table_delete_messages
 */
class m201123_180533_create_table_delete_messages extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%message_hide}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'time' => $this->string(),
            'chat_id' => $this->integer(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%message_hide}}');
    }
}
