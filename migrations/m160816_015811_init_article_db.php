<?php

use yii\db\Migration;

class m160816_015811_init_article_db extends Migration
{

    public function init()
    {
        $this->db = 'atDb';
        parent::init();
    }

    public function up()
    {
        $this->createTable('news_item', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->defaultValue(0),
            'type' => $this->smallInteger()->defaultValue(0),
            'style' => $this->smallInteger()->defaultValue(0),
            'relation_id' => $this->integer()->defaultValue(0),
            'title' => $this->string(1024)->defaultValue(''),
            'abstract' => $this->string(1024)->defaultValue(''),
            'pub_time' => $this->integer()->defaultValue(0),
            'link' => $this->text()->defaultValue(''),
            'cover_ids' => $this->string(255)->defaultValue(''),
            'media' => $this->integer()->defaultValue(0),
            'can_delete' => $this->smallInteger()->defaultValue(0),
            'is_hot' => $this->smallInteger()->defaultValue(0),
            'is_stick' => $this->smallInteger()->defaultValue(0),
            'be_hot_time' => $this->integer()->defaultValue(0),
            'on_stick_time' => $this->integer()->defaultValue(0),
            'off_stick_time' => $this->integer()->defaultValue(0),
            'label' => $this->string(64)->defaultValue(''),
        ]);


        $this->createTable('news_item_count', [
            'news_item_id' => 'pk',
            'like_count' => $this->integer()->defaultValue(0),
            'read_count' => $this->integer()->defaultValue(0),
            'dig_count' => $this->integer()->defaultValue(0),
            'bury_count' => $this->integer()->defaultValue(0),
            'comment_count' => $this->integer()->defaultValue(0),
        ]);


        $this->createTable('tt_article', [
            'article_id' => 'pk',
            'type' => $this->smallInteger()->defaultValue(0),
            'style' => $this->smallInteger()->defaultValue(0),
            'media_id' => $this->integer()->defaultValue(0),
            'cover_ids' => $this->string(64)->defaultValue(''),
        ]);


        $this->createTable('tt_article_count', [
            'article_id' => 'pk',
            'like_count' => $this->integer()->defaultValue(0),
            'read_count' => $this->integer()->defaultValue(0),
            'dig_count' => $this->integer()->defaultValue(0),
            'bury_count' => $this->integer()->defaultValue(0),
            'comment_count' => $this->integer()->defaultValue(0),
        ]);

        $this->createTable('tt_article_image', [
            'image_id' => 'pk',
            'tt_article_id' => $this->integer()->defaultValue(0),
            'sub_title' => $this->string(1024)->defaultValue(''),
            'sub_abstract' => $this->text()->defaultValue(''),
            'index' => $this->integer()->defaultValue(0),
            'tt_uri' => $this->string(1024)->defaultValue(''),
            'is_thumb' => $this->smallInteger()->defaultValue(0),
            'mode' => $this->smallInteger()->defaultValue(0),
        ]);

        $this->createTable('tt_article_tag', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->defaultValue(''),
        ]);

        $this->createTable('tt_article_tag_rel', [
            'id' => $this->primaryKey(),
            'article_id' => $this->integer()->defaultValue(0),
            'tag_id' => $this->integer()->defaultValue(0),
        ]);

        $this->createTable('tt_article_video', [
            'video_id' => 'pk',
            'tt_video_id' => $this->string(255)->defaultValue(''),
            'article_id' => $this->integer()->defaultValue(0),
            'create_time' => $this->integer()->defaultValue(0),
        ]);

        $this->createTable('tt_comment', [
            'comment_id' => 'pk',
            'key' => $this->string(255)->defaultValue(''),
        ]);

        $this->createTable('tt_media', [
            'id' => $this->primaryKey(),
            'tt_media_id' => $this->bigInteger()->defaultValue(0),
            'name' => $this->string(255)->defaultValue(''),
            'avatar' => $this->integer()->defaultValue(0),
            'description' => $this->text()->defaultValue(''),
        ]);
    }

    public function down()
    {
        $this->dropTable('news_item');
        $this->dropTable('news_item_count');
        $this->dropTable('tt_article');
        $this->dropTable('tt_article_count');
        $this->dropTable('tt_article_image');
        $this->dropTable('tt_article_tag');
        $this->dropTable('tt_article_tag_rel');
        $this->dropTable('tt_article_video');
        $this->dropTable('tt_comment');
        $this->dropTable('tt_media');
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
