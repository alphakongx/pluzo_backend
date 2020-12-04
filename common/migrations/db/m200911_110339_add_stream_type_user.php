<?php

use yii\db\Migration;

/**
 * Class m200911_110339_add_stream_type_user
 */
class m200911_110339_add_stream_type_user extends Migration
{
    public function up()
    {
        $this->addColumn('{{%stream_user}}', 'type', $this->integer()->Null());
        $this->addColumn('{{%stream_user}}', 'host', $this->integer()->Null());
    }

    public function down()
    {
        $this->dropColumn('{{%stream_user}}', 'type');
        $this->dropColumn('{{%stream_user}}', 'host');
    }
}
