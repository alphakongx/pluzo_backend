<?php

use yii\db\Migration;

/**
 * Class m201228_133133_add_country_city_state_to_swipe_setting
 */
class m201228_133133_add_country_city_state_to_swipe_setting extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%swipe_setting}}', 'country', $this->string()->Null());
        $this->addColumn('{{%swipe_setting}}', 'state', $this->string()->Null());
        $this->addColumn('{{%swipe_setting}}', 'city', $this->string()->Null());
        $this->addColumn('{{%swipe_setting}}', 'current_location', $this->string()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%swipe_setting}}', 'country');
        $this->dropColumn('{{%swipe_setting}}', 'state');
        $this->dropColumn('{{%swipe_setting}}', 'city');
        $this->dropColumn('{{%swipe_setting}}', 'current_location');
    }
}
