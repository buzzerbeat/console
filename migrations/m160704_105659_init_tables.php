<?php

use yii\db\Migration;
use yii\db\Schema;

class m160704_105659_init_tables extends Migration
{
    public function up()
    {

        $this->createTable('crawl_task', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->defaultValue(0),
            'command' => $this->string()->notNull(),
            'start_time' => $this->integer(),
            'end_time' => $this->integer(),
            'success_num' => $this->integer()->defaultValue(0),
            'fail_num' => $this->integer()->defaultValue(0),
            'filter_num' => $this->integer()->defaultValue(0),
            'duplicate_num' => $this->integer()->defaultValue(0),
            'error_json' => $this->text()->notNull(),
        ]);

        $this->createTable('crawl_thread', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->defaultValue(0),
            'status' => $this->smallInteger()->defaultValue(0),
            'site' => $this->string()->defaultValue(''),
            'url' => $this->string()->defaultValue(''),
            'key' => $this->string()->defaultValue(''),
            'time' => $this->integer(),
            'duration' => $this->integer()->defaultValue(0),
            'entity_id' => $this->string()->notNull(),
            'error_json' => $this->text()->notNull(),
        ]);

        $this->createTable('image', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->defaultValue(0),
            'desc' => $this->string()->notNull(),
            'file_path' => $this->string()->notNull(),
            'add_time' => $this->integer()->defaultValue(0),
            'update_time' => $this->integer()->defaultValue(0),
            'width' => $this->integer()->defaultValue(0),
            'height' => $this->integer()->defaultValue(0),
            'mime' => $this->string()->notNull(),
            'md5' => $this->string()->notNull(),
            'size' => $this->integer()->defaultValue(0),
            'dynamic' => $this->smallInteger()->defaultValue(0),
        ]);

        $this->createTable('video', [
            'id' => $this->primaryKey(),
            'status' => $this->smallInteger()->defaultValue(0),
            'key' => $this->string()->defaultValue(''),
            'desc' => $this->string()->notNull(),
            'cover_img' => $this->string()->notNull(),
            'length' => $this->integer()->defaultValue(0),
            'width' => $this->integer()->defaultValue(0),
            'height' => $this->integer()->defaultValue(0),
            'size' => $this->integer()->defaultValue(0),
            'add_time' => $this->integer()->defaultValue(0),
            'pub_time' => $this->integer()->defaultValue(0),
            'watermark' => $this->smallInteger()->defaultValue(0),
            'url' => $this->string()->notNull(),
            'm3u8_url' => $this->string()->notNull(),
            'local' => $this->string()->notNull(),
            'regex_setting' => $this->integer(),
            'site_url' => $this->string()->notNull(),
        ]);

        $this->createTable('app', [
            'id' => $this->primaryKey(),
            'key' => $this->string()->notNull(),
            'name' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
        ]);

        $this->createTable('comment', [
            'id' => $this->primaryKey(),
            'content' => $this->string(2048)->notNull(),
            'status' => $this->smallInteger()->notNull(),
            'client_id' => $this->string()->notNull(),
            'resource_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'user_ip' => $this->string()->notNull(),
            'user_agent' => $this->string(1024)->notNull(),
            'parent' => $this->integer()->notNull(),
            'create_time' => $this->integer()->notNull(),
        ]);

        $this->createTable('site_regex_setting', [
            'id' => $this->primaryKey(),
            'site' => $this->string()->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'app_req_url' => $this->string()->notNull(),
            'pattern' => $this->string()->notNull(),
            'matches_index' => $this->string()->notNull(),
            'headers' => $this->text()->notNull(),
        ]);

        $this->createTable('oauth_refresh_tokens', [

            'id' => $this->primaryKey(),
            'refresh_token' => $this->string()->notNull(),
            'client_id' => $this->string()->notNull(),
            'user_id' => $this->string()->notNull(),
            'expires' => Schema::TYPE_TIMESTAMP . ' on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'scope' => $this->text()->notNull(),

        ]);

        $this->createTable('oauth_clients', [
            'id' => $this->primaryKey(),
            'client_id' => $this->string()->notNull(),
            'client_secret' => $this->string()->notNull(),
            'redirect_uri' => $this->text()->notNull(),
            'grant_types' => $this->string()->notNull(),
            'scope' => $this->text()->notNull(),
            'user_id' => $this->string()->notNull(),
        ]);

        $this->createTable('oauth_access_tokens', [
            'id' => $this->primaryKey(),
            'access_token' => $this->string()->notNull(),
            'client_id' => $this->string()->notNull(),
            'user_id' =>$this->string()->notNull(),
            'expires' => Schema::TYPE_TIMESTAMP . ' on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'scope' => $this->text()->notNull(),
        ]);


        $this->addColumn('user', 'device_uuid', $this->string()->notNull());
        $this->addColumn('user', 'type', $this->smallInteger()->defaultValue(0));
        $this->addColumn('user', 'salt', $this->string()->notNull());
        $this->addColumn('user', 'sex', $this->smallInteger()->defaultValue(0));
        $this->addColumn('user', 'avatar', $this->integer()->defaultValue(0));
        $this->addColumn('user', 'qq', $this->string()->notNull());
        $this->addColumn('user', 'weixin', $this->string()->notNull());
        $this->addColumn('user', 'weibo', $this->string()->notNull());
        $this->addColumn('user', 'mobile', $this->string()->notNull());
        $this->addColumn('user', 'personal_sign', $this->text()->notNull());
        $this->addColumn('user', 'client_id', $this->string()->notNull());
    }

    public function down()
    {
        $this->dropTable('crawl_task');
        $this->dropTable('crawl_thread');
        $this->dropTable('image');
        $this->dropTable('device_uuid');
        $this->dropTable('video');
        $this->dropTable('site_regex_setting');
        $this->dropTable('comment');
        $this->dropTable('app');
        $this->dropTable('oauth_refresh_tokens');
        $this->dropTable('oauth_clients');
        $this->dropTable('oauth_access_tokens');

        $this->dropColumn('user', 'type');
        $this->dropColumn('user', 'salt');
        $this->dropColumn('user', 'sex');
        $this->dropColumn('user', 'avatar');
        $this->dropColumn('user', 'qq');
        $this->dropColumn('user', 'weixin');
        $this->dropColumn('user', 'weibo');
        $this->dropColumn('user', 'mobile');
        $this->dropColumn('user', 'personal_sign');
        $this->dropColumn('user', 'client_id');
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
