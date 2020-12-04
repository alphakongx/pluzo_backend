<?php

use yii\db\Migration;

/**
 * Class m201023_205122_advance_add_boost_type
 */
class m201023_205122_advance_add_boost_type extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%advance}}', 'boost_type', $this->integer()->Null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%advance}}', 'boost_type');
    }
}
