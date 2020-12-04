<?php

use yii\db\Migration;

/**
 * Class m201008_130231_create_payment
 */
class m201008_130231_create_payment extends Migration
{
        public function safeUp()
    {
        $this->createTable('{{%payment}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer(),
            'time' => $this->string(),
            'payment_method' => $this->string(),
            'transaction_id' => $this->string(),
            'status' => $this->integer(),
            'service_id' => $this->integer(),
            'amount' => $this->decimal(10, 2),
        ]);

        $this->createTable('{{%service}}', [
            'id' => $this->primaryKey(),
            'price' => $this->decimal(10, 2),
            'during' => $this->integer(),
            'name' => $this->string(),
            'discont' => $this->string(),
            'description' => $this->string(),
            'count' => $this->string(),
            'type' => $this->integer(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%payment}}');
        $this->dropTable('{{%service}}');
    }
}
