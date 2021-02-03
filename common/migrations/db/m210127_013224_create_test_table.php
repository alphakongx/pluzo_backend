<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%test}}`.
 */
class m210127_013224_create_test_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%test}}', [
            'id' => $this->primaryKey(),
            'text' => $this->text()->Null(),
            'time' => $this->string()->Null(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%test}}');
    }
}
