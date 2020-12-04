<?php

use yii\db\Migration;

/**
 * Class m200929_132826_create_table_indicator
 */
class m200929_132826_create_table_indicator extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%indicator}}', [
            'id' => $this->primaryKey(),
            'user_current_id' => $this->integer(),
            'user_target_id' => $this->integer(),
            'time' => $this->string(),
            'type' => $this->integer(),
            'status' => $this->integer(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%indicator}}');
    }
}
