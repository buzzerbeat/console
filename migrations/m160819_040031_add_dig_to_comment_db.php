<?php

use yii\db\Migration;

class m160819_040031_add_dig_to_comment_db extends Migration
{
    public function up()
    {
        $this->addColumn('comment', 'dig', $this->integer()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('comment', 'dig');
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
