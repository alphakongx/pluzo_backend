<?php

use yii\db\Migration;

/**
 * Class m201103_021639_client_setting
 */
class m201103_021639_client_setting extends Migration
{
        /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%client_setting}}', [
            'id' => $this->primaryKey(),            
            'user_id' => $this->integer(),
            'push_new_friend' => $this->integer()->Null(),
            'push_friend_request' => $this->integer()->Null(),
            'push_live' => $this->integer()->Null(),
            'push_message' => $this->integer()->Null(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%client_setting}}');
    }
}
