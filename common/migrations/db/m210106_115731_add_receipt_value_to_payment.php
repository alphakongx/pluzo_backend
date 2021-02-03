<?php

use yii\db\Migration;

/**
 * Class m210106_115731_add_receipt_value_to_payment
 */
class m210106_115731_add_receipt_value_to_payment extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%payment}}', 'receipt', $this->text()->Null());
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%payment}}', 'receipt');
    }
}
