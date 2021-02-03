<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%live_setting}}`.
 */
class m201229_083858_create_live_setting_table extends Migration
{   
    public function safeUp()
    {
        $this->createTable('{{%live_setting}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'country' => $this->string()->Null(),
            'state' => $this->string()->Null(),
            'filter' => $this->integer()->defaultValue(0),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%live_setting}}');
    }
}
