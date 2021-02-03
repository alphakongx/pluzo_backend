<?php

use yii\db\Migration;

/**
 * Class m201205_053704_add_hide_swipe
 */
class m201205_053704_add_hide_swipe extends Migration
{
public function safeUp()
    {
        $this->addColumn('{{%swipe_setting}}', 'hide', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%swipe_setting}}', 'hide');
    }
}
