<?php

use yii\db\Migration;

/**
 * Class m210330_062430_add_new_fields
 */
class m210330_062430_add_new_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%client}}', 'hide_city', $this->integer()->defaultValue(0));
        $this->addColumn('{{%client_setting}}', 'push_likes', $this->integer()->Null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%client}}', 'hide_city');
        $this->dropColumn('{{%client_setting}}', 'push_likes');
    }
}
