<?php

use yii\db\Migration;

/**
 * Class m200907_161617_add_message_type
 */
class m200907_161617_add_message_type extends Migration
{
    public function up()
    {
        $this->addColumn('{{%message}}', 'type', $this->string()->Null());
        $this->addColumn('{{%message}}', 'channel_id', $this->string()->Null());
    }

    public function down()
    {
        $this->dropColumn('{{%message}}', 'type');
        $this->dropColumn('{{%message}}', 'channel_id');
    }
}
