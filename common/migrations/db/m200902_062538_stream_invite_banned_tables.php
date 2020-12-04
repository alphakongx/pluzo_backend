<?php

use yii\db\Migration;

/**
 * Class m200902_062538_stream_invite_banned_tables
 */
class m200902_062538_stream_invite_banned_tables extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%stream_invite}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'channel_id' => $this->string()->notNull(),
            'created_at' => $this->string()->Null(),
        ], $tableOptions);

        $this->createTable('{{%stream_ban}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'channel_id' => $this->string()->notNull(),
            'created_at' => $this->string()->Null(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%stream_invite}}');
        $this->dropTable('{{%stream_ban}}');
    }
}
