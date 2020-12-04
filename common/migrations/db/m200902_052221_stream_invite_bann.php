<?php

use yii\db\Migration;

/**
 * Class m200902_052221_stream_invite_bann
 */
class m200902_052221_stream_invite_bann extends Migration
{
    public function up()
    {
        $this->addColumn('{{%stream}}', 'invite_only', $this->string()->Null()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('{{%stream}}', 'invite_only');
    }
}
