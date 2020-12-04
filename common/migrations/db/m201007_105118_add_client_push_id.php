<?php

use yii\db\Migration;

/**
 * Class m201007_105118_add_client_push_id
 */
class m201007_105118_add_client_push_id extends Migration
{
      /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%client}}', 'push_id', $this->string()->Null());
        $this->addColumn('{{%client}}', 'device', $this->integer()->Null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%client}}', 'push_id');
        $this->dropColumn('{{%client}}', 'device');
    }
}
