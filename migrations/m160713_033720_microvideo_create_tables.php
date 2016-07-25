<?php

use yii\db\Migration;

class m160713_033720_microvideo_create_tables extends Migration
{
    public function init()
    {
        $this->db = 'mvDb';
        parent::init();
    }

    public function up()
    {
        $this->createTable('mv_video', [
            'id' => $this->primaryKey(),
            'video_id' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'key' => $this->string()->notNull(),
            'title' => $this->string()->notNull(),
            'desc' => $this->text()->notNull(),
            'source_url' => $this->string()->notNull(),
            'create_time' => $this->integer()->notNull(),
            'update_time' => $this->integer()->notNull(),
        ]);

        $this->createTable('mv_category', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'keyword' => $this->string()->notNull(),
            'rank' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createTable('mv_video_category_rel', [
            'id' => $this->primaryKey(),
            'video_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->notNull(),
        ]);


        $this->createTable('mv_keyword', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'rank' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createTable('mv_video_keyword_rel', [
            'id' => $this->primaryKey(),
            'video_id' => $this->integer()->notNull(),
            'keyword_id' => $this->integer()->notNull(),
        ]);

        $this->createTable('mv_video_count', [
            'video_id' => 'pk',
            'like' => $this->integer()->notNull(),
            'dig' => $this->integer()->notNull(),
            'played' => $this->integer()->notNull(),
            'bury' => $this->integer()->notNull(),
            'share' => $this->integer()->notNull(),
        ]);
    }

    public function down()
    {
        $this->dropTable('mv_video');
        $this->dropTable('mv_keyword');
        $this->dropTable('mv_video_keyword_rel');
        $this->dropTable('mv_video_count');
        $this->dropTable('mv_category');
        $this->dropTable('mv_video_category_rel');
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
