<?php

use yii\db\Migration;

class m160725_074251_append_fav_to_vc_table extends Migration
{

    public function init()
    {
        $this->db = 'mvDb';
        parent::init();
    }

    public function up()
    {
        $this->addColumn('mv_video_count', 'fav', $this->integer()->notNull());

        $this->createTable('mv_video_like', [
            'id' => $this->primaryKey(),
            'video_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'like' => $this->smallInteger()->notNull(),
            'time' => $this->integer()->notNull(),
        ]);

        $this->createTable('mv_video_fav', [
            'id' => $this->primaryKey(),
            'video_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'fav' => $this->smallInteger()->notNull(),
            'time' => $this->integer()->notNull(),
        ]);
    }

    public function down()
    {
        $this->dropColumn('mv_video_count', 'fav');
        $this->dropTable('mv_video_like');
        $this->dropTable('mv_video_fav');
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
