<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%report}}`.
 */
class m201108_075352_create_report_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%report}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'type' => $this->integer(),
            'msg' => $this->text()->Null(),
            'reason' => $this->integer(),
            'channel' => $this->string(),
            'time' => $this->string(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%report}}');
    }
}
