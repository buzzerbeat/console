<?php

use yii\db\Migration;

class m160706_124637_wallpaper_create_tables extends Migration
{

    public function init()
    {
        $this->db = 'wpDb';
        parent::init();
    }

    public function up()
    {
        $this->createTable('album', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->defaultValue(0),
            'title' => $this->string()->notNull(),
            'icon' => $this->string()->notNull(),
            'section' => $this->string()->notNull(),
            'category' => $this->integer()->notNull(),
            'key' => $this->string()->notNull(),
            'create_time' => $this->integer(),

        ]);

        $this->createTable('album_img_rel', [
            'id' => $this->primaryKey(),
            'album_id' => $this->integer()->notNull(),
            'wp_img_id' => $this->integer()->notNull(),
        ]);

        $this->createTable('category', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'keyword' => $this->string()->notNull(),
            'rank' => $this->integer()->notNull(),
        ]);

        $this->createTable('wp_image', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->notNull(),
            'img_id' => $this->integer()->notNull(),
            'desc' => $this->string()->notNull(),
            'source_url' => $this->string()->notNull(),
        ]);
//
//        $this->createTable('tag', [
//            'id' => $this->primaryKey(),
//            'name' => $this->integer()->notNull(),
//            'img_id' => $this->integer()->notNull(),
//        ]);
//
//        $this->createTable('album_tag_rel', [
//            'id' => $this->primaryKey(),
//            'album_id' => $this->integer()->notNull(),
//            'img_id' => $this->integer()->notNull(),
//        ]);
    }

    public function down()
    {
        $this->dropTable('album');
        $this->dropTable('album_img_rel');
        $this->dropTable('wp_image');
        $this->dropTable('category');

//        return false;
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
