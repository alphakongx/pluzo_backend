<?php

use yii\db\Migration;

/**
 * Class m201228_001115_create_table_premium_use
 */
class m201228_001115_create_table_premium_use extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%premium_use}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
            'boost_type' => $this->string()->Null(),
            'time' => $this->string()->Null(),
            'premium_id' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%premium_use}}');
    }
}
