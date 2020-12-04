<?php

use yii\db\Migration;

/**
 * Class m201126_195028_add_first_login_to_client
 */
class m201126_195028_add_first_login_to_client extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%client}}', 'first_login', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%client}}', 'first_login');
    }
}
