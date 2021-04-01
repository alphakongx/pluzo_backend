<?php

use yii\db\Migration;

/**
 * Class m210329_041901_user_hide_loc_and_like
 */
class m210329_041901_user_hide_loc_and_like extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%client}}', 'hide_location', $this->integer()->defaultValue(0));
        $this->addColumn('{{%client}}', 'likes', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%client}}', 'hide_location');
        $this->dropColumn('{{%client}}', 'likes');
    }
}
