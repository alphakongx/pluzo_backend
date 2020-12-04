<?php

use yii\db\Migration;

/**
 * Class m201001_110058_user_add_city
 */
class m201001_110058_user_add_city extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%client}}', 'city', $this->string()->Null());
        $this->addColumn('{{%client}}', 'state', $this->string()->Null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%client}}', 'city');
        $this->dropColumn('{{%client}}', 'state');
    }

}
