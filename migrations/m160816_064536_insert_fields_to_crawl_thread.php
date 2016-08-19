<?php

use yii\db\Migration;

class m160816_064536_insert_fields_to_crawl_thread extends Migration
{
    public function up()
    {
        $this->addColumn('crawl_thread', 'total_num', $this->integer()->defaultValue(0));
        $this->addColumn('crawl_thread', 'success_num', $this->integer()->defaultValue(0));
        $this->addColumn('crawl_thread', 'fail_num', $this->integer()->defaultValue(0));
        $this->addColumn('crawl_thread', 'duplicate_num', $this->integer()->defaultValue(0));
        $this->addColumn('crawl_thread', 'filter_num', $this->integer()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('crawl_thread', 'filter_num');
        $this->dropColumn('crawl_thread', 'duplicate_num');
        $this->dropColumn('crawl_thread', 'fail_num');
        $this->dropColumn('crawl_thread', 'success_num');
        $this->dropColumn('crawl_thread', 'total_num');
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
