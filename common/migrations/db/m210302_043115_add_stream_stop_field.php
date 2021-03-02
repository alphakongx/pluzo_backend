<?php

use yii\db\Migration;

/**
 * Class m210302_043115_add_stream_stop_field
 */
class m210302_043115_add_stream_stop_field extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%stream}}', 'stop', $this->integer()->Null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%stream}}', 'stop');
    }
}
