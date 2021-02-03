<?php

use yii\db\Migration;

/**
 * Class m210113_190057_add_advance_live_boost_info
 */
class m210113_190057_add_advance_live_boost_info extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%advance}}', 'channel_id', $this->string()->Null());
        $this->addColumn('{{%premium_use}}', 'channel_id', $this->string()->Null());
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%advance}}', 'channel_id');
        $this->dropColumn('{{%premium_use}}', 'channel_id');
    }
}
