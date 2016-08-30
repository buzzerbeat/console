<?php

use yii\db\Migration;

/**
 * Handles the creation for table `article_fav_and_like`.
 */
class m160830_095145_create_article_fav_and_like_table extends Migration
{

    public function init()
    {
        $this->db = 'atDb';
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->addColumn('tt_article_count', 'fav_count', $this->integer()->notNull()->defaultValue(0));

        $this->createTable('tt_article_like', [
            'id' => $this->primaryKey(),
            'article_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'like' => $this->smallInteger()->notNull(),
            'time' => $this->integer()->notNull(),
        ]);

        $this->createTable('tt_article_fav', [
            'id' => $this->primaryKey(),
            'article_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->integer()->notNull()->defaultValue(0),
            'fav' => $this->smallInteger()->notNull(),
            'time' => $this->integer()->notNull(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('tt_article_fav');
        $this->dropTable('tt_article_like');
        $this->dropColumn('tt_article_count', 'fav_count');
    }
}
