<?php

use yii\db\Migration;

class m160722_070420_add_item_type_to_comment extends Migration
{
    public function up()
    {
        $this->addColumn('comment', 'item_type', $this->string()->notNull());
        $this->renameColumn('comment', 'resource_id', 'item_id');
    }

    public function down()
    {
        $this->dropColumn('comment', 'item_type');
        $this->renameColumn('comment', 'item_id', 'resource_id');
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
