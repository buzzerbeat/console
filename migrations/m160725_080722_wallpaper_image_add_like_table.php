<?php

use yii\db\Migration;

class m160725_080722_wallpaper_image_add_like_table extends Migration
{

    public function init()
    {
        $this->db = 'wpDb';
        parent::init();
    }

    public function up()
    {

        $this->createTable('album_fav', [
            'id' => $this->primaryKey(),
            'album_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'fav' => $this->smallInteger()->notNull(),
        ]);

        $this->createTable('wp_image_like', [
            'id' => $this->primaryKey(),
            'wp_image_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'like' => $this->smallInteger()->notNull(),
        ]);

        $this->createTable('wp_image_fav', [
            'id' => $this->primaryKey(),
            'wp_image_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'fav' => $this->smallInteger()->notNull(),
        ]);
    }

    public function down()
    {
        $this->dropTable('album_fav');
        $this->dropTable('wp_image_like');
        $this->dropTable('wp_image_fav');
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
