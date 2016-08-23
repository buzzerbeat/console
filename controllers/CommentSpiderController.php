<?php 


namespace console\controllers;

use yii\helpers\Json;
use Exception;
use linslin\yii2\curl;
use common\components\Utility;
use common\models\Video;
use microvideo\models\MvVideo;
use console\controllers\BaseController;
use console\models\CommentForm;

class CommentSpiderController extends BaseController{
    
    public function actionYidian(){
        
        $videos = Video::find()->where(['like', '`key`', 'yidian%', false])->limit(2)->orderBy('id desc')->all();
        $host = $this->yidianHost;
        $clientId = 'collect/yidian';
        $itemType = 'microvideo/video';
        $site = 'yidian';
        $cookie = 'JSESSIONID=rAkNO220FNIyfp5dNAWCLQ';
        $header = ['Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache', "Cookie:$cookie"];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, $header);
        foreach($videos as $video){
        	$key = end(explode('/', $video->key));
        	$commentUrl = "{$host}/Website/contents/comments?platform=1&appid=yidian&docid={$key}&cv=3.6.8&count=30&distribution=zhushou.360.cn&version=020107&net=wifi";
        	$commentContent = $curl->get($commentUrl);
            $commentInfo = Json::decode($commentContent, true);
            $hotComment = isset($commentInfo['hot_comments']) ? $commentInfo['hot_comments'] : [];
            $normalComment = $commentInfo['comments'];
            $hotNum = count($hotComment);
            $commentList = array_merge($hotComment, $normalComment);
            $mvVideo = MvVideo::findOne(['video_id'=>$video->id]);
            foreach($commentList as $key=>$comt){
            	
            	$info = [
                    'content'=>$comt['comment'],
                    'create_time'=>strtotime($comt['createAt']),
                    'uname'=>$comt['nickname'],
                    'dig'=>$comt['like'],
                    'uavatar'=>$comt['profile'],
                    'item_id'=>!empty($mvVideo) ? $mvVideo->id : 0,
                    'item_type'=>$itemType,
                    'client'=>$clientId,
                    'site'=>$site,
                    'is_hot'=>($key>$hotNum) ? 0 : 1,
            	];
            	$form = new CommentForm();
            	if(!$form->load(['CommentForm'=>$info]) || !$form->save()){
            		print_r($form->getErrors());
            	}
            	else{
            		echo "增加用户" . $comt['nickname'] . "增加评论：" . $comt['comment'] . "\n";
            	}
            }
        }
    }
    
    public function actionToutiao(){
        $videos = Video::find()->where(['like', '`key`', 'toutiao%', false])->limit(10)->orderBy('id desc')->all();
        $host = 'http://ic.snssdk.com';
        $clientId = 'collect/toutiao';
        $itemType = 'microvideo/video';
        $site = 'toutiao';
        $header = ['Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, $header);
        foreach($videos as $video){
            $urlarr = explode('/', $video->site_url);
            $key = end(array_filter($urlarr));
            $commentUrl = "{$host}/article/v1/tab_comments/?group_id={$key}&item_id={$key}&aggr_type=1&count=20&offset=0";
            $commentUrl .= "&tab_index=0&iid=4773904108&device_id=4052707364&ac=wifi&channel=360&aid=13&app_name=news_article&version_code=562&version_name=5.6.2&device_platform=android&ab_version=ttuid_abtest_channel_base2%2Cttuid_abtest_article_base2%2Cttuid_abtest_stream_base2&ab_client=a1%2Cc2%2Ce1%2Cf1%2Cg2%2Cb8%2Cf5&ab_group=z1&ab_feature=z1&abflag=1&ssmix=a&device_type=TianTian&device_brand=TTAndroid&language=zh&os_api=18&os_version=4.3&uuid=475332833430937&openudid=86762c9b445b6849&manifest_version_code=562&resolution=480*854&dpi=160&update_version_code=5622&_rticket=1470639579278";
            $commentContent = $curl->get($commentUrl);
            $commentInfo = Json::decode($commentContent, true);

            $commentList = $commentInfo['data'];
            $mvVideo = MvVideo::findOne(['video_id'=>$video->id]);
            foreach($commentList as $comment){
                $comt = $comment['comment'];
                $info = [
                    'content'=>$comt['text'],
                    'create_time'=>$comt['create_time'],
                    'uname'=>$comt['user_name'],
                    'dig'=>$comt['digg_count'],
                    'uavatar'=>$comt['user_profile_image_url'],
                    'item_id'=>!empty($mvVideo) ? $mvVideo->id : 0,
                    'item_type'=>$itemType,
                    'client'=>$clientId,
                    'site'=>$site,
                    'is_hot'=>0,
                ];
                $form = new CommentForm();
                if(!$form->load(['CommentForm'=>$info]) || !$form->save()){
                    print_r($form->getErrors());
                }
                else{
                    echo "增加用户" . $comt['user_name'] . "增加评论：" . $comt['text'] . "\n";
                }
            }
        }
    }
    
    public function actionMeipai(){
        $videos = Video::find()->where(['like', '`key`', 'meipai%', false])->limit(10)->orderBy('id desc')->all();
        $host = 'http://www.meipai.com';
        $clientId = 'collect/meipai';
        $itemType = 'microvideo/video';
        $site = 'meipai';
        $header = ['Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, $header);
        foreach($videos as $video){
            $urlarr = explode('/', $video->key);
            $key = end($urlarr);
            $commentUrl = "{$host}/medias/comments_timeline?page=1&count=50&id=$key";
            $commentContent = $curl->get($commentUrl);
            $commentList = Json::decode($commentContent, true);

            $mvVideo = MvVideo::findOne(['video_id'=>$video->id]);
            foreach($commentList as $comt){
                $info = [
                    'content'=>$comt['content'],
                    'create_time'=>isset($comt['created_at_origin']) ? $comt['created_at_origin'] : time(),
                    'uname'=>$comt['user']['screen_name'],
                    'dig'=>$comt['liked_count'],
                    'uavatar'=>$comt['user']['avatar'],
                    'item_id'=>!empty($mvVideo) ? $mvVideo->id : 0,
                    'item_type'=>$itemType,
                    'client'=>$clientId,
                    'site'=>$site,
                    'is_hot'=>0,
                ];
                $form = new CommentForm();
                if(!$form->load(['CommentForm'=>$info]) || !$form->save()){
                    print_r($form->getErrors());
                }
                else{
                    echo "增加用户" . $comt['user']['screen_name'] . "增加评论：" . $comt['content'] . "\n";
                }
            }
        }
    }
    
    public function actionMiaopai(){
        $videos = Video::find()->where(['like', '`key`', 'miaopai%', false])->limit(1)->orderBy('id desc')->all();
        $host = 'http://www.miaopai.com';
        $clientId = 'collect/miaopai';
        $itemType = 'microvideo/video';
        $site = 'miaopai';
        $header = ['Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, $header);
        foreach($videos as $video){
            $urlarr = explode('/', $video->key);
            $key = end($urlarr);
            $commentUrl = "{$host}/miaopai/get_v2_comments?scid={$key}&per=50&page=1";
            $commentContent = $curl->get($commentUrl);
            if(preg_match_all('/<a title=\'([^\']+)\'[^>]+>[\s\t]*<img src="([^"]+)"[^>]+>.*<strong>([^<]+)<\/strong>[\s\t]*<span[^>]+>(.*)<\/span>/siU', $commentContent, $commentList, PREG_SET_ORDER)){
                $mvVideo = MvVideo::findOne(['video_id'=>$video->id]);
                foreach($commentList as $comt){
                    $info = [
                        'content'=>strip_tags($comt[4]),
                        'create_time'=>time(),
                        'uname'=>$comt[1],
                        'dig'=>0,
                        'uavatar'=>$comt[2],
                        'item_id'=>!empty($mvVideo) ? $mvVideo->id : 0,
                        'item_type'=>$itemType,
                        'client'=>$clientId,
                        'site'=>$site,
                        'is_hot'=>0,
                    ];
                    $form = new CommentForm();
                    if(!$form->load(['CommentForm'=>$info]) || !$form->save()){
                        print_r($form->getErrors());
                    }
                    else{
                        echo "增加用户" . $comt[4] . "增加评论：" . strip_tags($comt[4]) . "\n";
                    }
                }
            }
            else{
                echo "解析页面{$commentUrl}内容失败\n";	
            }
            
        }
    }
    
    public function actionNetease(){
        echo 'todo';
        exit;
        $videos = Video::find()->where(['like', '`key`', 'netease%', false])->limit(1)->orderBy('id desc')->all();
        $host = 'http://sdk.comment.163.com/';
        $clientId = 'collect/netease';
        $itemType = 'microvideo/video';
        $site = 'netease';
        $header = ['Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache'];
        $curl = new curl\Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, $header);
        foreach($videos as $video){
            $siteUrl = $video->site_url;
            $siteContent = $curl->get($siteUrl);
            //通过$siteContent获取docId
            
            $commentUrl = "{$host}/api/v1/products/a2869674571f77b5a0867c3d71db5856/threads/BT6J5SQ6008535RB/comments/newList?callback=bowlder.cb._2&limit=50&showLevelThreshold=72&headLimit=1&tailLimit=2&offset=0&ibc=jssdk";
            $commentContent = $curl->get($commentUrl);
            $commentList = Json::decode($commentContent, true);
        
            $mvVideo = MvVideo::findOne(['video_id'=>$video->id]);
            foreach($commentList as $comt){
                $info = [
                    'content'=>$comt['content'],
                    'create_time'=>isset($comt['created_at_origin']) ? $comt['created_at_origin'] : time(),
                    'uname'=>$comt['user']['screen_name'],
                    'dig'=>$comt['liked_count'],
                    'uavatar'=>$comt['user']['avatar'],
                    'item_id'=>!empty($mvVideo) ? $mvVideo->id : 0,
                    'item_type'=>$itemType,
                    'client'=>$clientId,
                    'site'=>$site,
                    'is_hot'=>0,
                ];
                $form = new CommentForm();
                if(!$form->load(['CommentForm'=>$info]) || !$form->save()){
                    print_r($form->getErrors());
                }
                else{
                    echo "增加用户" . $comt['user']['screen_name'] . "增加评论：" . $comt['content'] . "\n";
                }
            }
        }
    }
}
