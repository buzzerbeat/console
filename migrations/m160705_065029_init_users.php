<?php

use yii\db\Migration;

class m160705_065029_init_users extends Migration
{
    public function up()
    {
        $this->insert('user', [
            'username' => 'jobbr',
            'password_hash' => Yii::$app->security->generatePasswordHash('880304'),
            'password_reset_token' => Yii::$app->security->generateRandomString() . '_' . time(),
            'email' => "",
            'status' => 1,
            'type' => 0,
        ]);
    }

    public function down()
    {
        echo "m160705_065029_init_users cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
