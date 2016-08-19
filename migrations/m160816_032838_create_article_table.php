<?php

use yii\db\Migration;

/**
 * Handles the creation for table `article`.
 */
class m160816_032838_create_article_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('article', [
            'id' => $this->primaryKey(),
            'title' => $this->string(1024)->defaultValue(''),
            'abstract' => $this->string(1024)->defaultValue(''),
            'content' => $this->text()->defaultValue(''),
            'src_link' => $this->string(2048)->defaultValue(''),
            'source' => $this->string(255)->defaultValue(''),
            'key' => $this->string(255)->defaultValue(''),
            'pub_time' => $this->integer()->defaultValue(0),
            'cover' => $this->integer()->defaultValue(0),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('article');
    }
}
