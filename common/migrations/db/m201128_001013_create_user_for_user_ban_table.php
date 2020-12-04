<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_for_user_ban}}`.
 */
class m201128_001013_create_user_for_user_ban_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ban_user}}', [
            'id' => $this->primaryKey(),
            'user_source_id' => $this->integer()->notNull(),
            'user_target_id' => $this->integer()->notNull(),
            'reason' => $this->string()->Null(),
            'time' => $this->string()->Null(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%ban_user}}');
    }
}
