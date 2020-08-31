<?php

use yii\db\Migration;

/**
 * Class m200826_074843_create_table_stream_ask_join
 */
class m200826_074843_create_table_stream_ask_join extends Migration
{
        public function safeUp()
    {
        $this->createTable('{{%stream_ask}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'created_at' => $this->string(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'channel_id' => $this->string(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%stream_ask}}');
    }
}
