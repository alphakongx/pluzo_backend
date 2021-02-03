<?php

use yii\db\Migration;

/**
 * Class m210203_101949_create_analytics
 */
class m210203_101949_create_analytics extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%analit}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'page' => $this->string()->Null(),
            'time' => $this->string()->Null(),
            'time_start' => $this->string()->Null(),
            'time_end' => $this->string()->Null(),
            'during' => $this->string()->Null(),
            'leave' => $this->integer()->Null(),
        ]);

        $this->createTable('{{%friend_removed}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'user_target_id' => $this->integer(),
            'time' => $this->string()->Null(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%analit}}');
        $this->dropTable('{{%friend_removed}}');
    }
}
