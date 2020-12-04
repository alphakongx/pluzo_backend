<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%swipe_setting}}`.
 */
class m200928_072042_create_swipe_setting_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%swipe_setting}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'global' => $this->integer()->Null(),
            'latitude' => $this->string()->Null(),
            'longitude' => $this->string()->Null(),
            'location' => $this->string()->Null(),
            'age_from' => $this->integer()->Null(),
            'age_to' => $this->integer()->Null(),
            'gender' => $this->integer()->Null(),
            'distance' => $this->string()->Null(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%swipe_setting}}');
    }
}
