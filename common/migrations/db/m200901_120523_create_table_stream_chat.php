<?php

use yii\db\Migration;

/**
 * Class m200901_120523_create_table_stream_chat
 */
class m200901_120523_create_table_stream_chat extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%stream_chat}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'created_at' => $this->string(),
            'text' => $this->text(),
            'channel_id' => $this->string(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%stream_chat}}');
    }
}
