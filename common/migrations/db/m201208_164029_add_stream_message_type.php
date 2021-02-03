<?php

use yii\db\Migration;

/**
 * Class m201208_164029_add_stream_message_type
 */
class m201208_164029_add_stream_message_type extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%stream_chat}}', 'type', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%stream_chat}}', 'type');
    }
}
