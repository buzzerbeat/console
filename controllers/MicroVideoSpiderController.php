<?php
/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/7/13
 * Time: 11:17
 */

namespace console\controllers;


use common\components\Utility;
use common\models\ImageForm;
use common\models\SiteRegexSetting;
use common\models\Video;
use Exception;
use microvideo\models\MvCategory;
use microvideo\models\MvKeyword;
use microvideo\models\MvVideo;
use microvideo\models\MvVideoCategoryRel;
use microvideo\models\MvVideoCount;
use microvideo\models\MvVideoKeywordRel;
use microvideo\models\MvTag;
use yii\console\Controller;
use linslin\yii2\curl;
use yii\helpers\Json;
use app\models\Tag;
use backend\models\microvideo\MvTagRel;
use backend\models\microvideo\MvVideoTagRel;

class MicroVideoSpiderController extends BaseController
{

    static $catArr = [
        'video' => '推荐',
        'subv_voice' => '好声音',
        'subv_funny' => '搞笑',
        'subv_society' => '社会',
        'subv_boutique' => '原创',
        'subv_comedy' => '小品',
        'subv_cute' => '萌物',
        'subv_entertainment' => '娱乐',
        'subv_beauty' => '美女',
        'subv_movie' => '影视',
        'subv_broaden_view' => '开眼',
        'subv_life' => '生活'
    ];

    static $netEaseCatArr = [
        'T1457069041911' => '搞笑',
        'T1457069205071' => '新闻',
        'T1457069261743' => '八卦',
        'T1457069319264' => '猎奇',
        'T1457069232830' => '萌物',
        'T1457069080899' => '美女帅哥',
        'T1457069346235' => '体育',
        'T1457069387259' => '黑科技',
        'T1457069475980' => '涨姿势',
        'T1464751736259' => '音乐',
        'T1457069446903' => '二次元',
        'T1457069421892' => '军武',
        'T1461563165622' => '全景'
    ];


    static $miaoPaiCatArr = [
        132=>'女神视频',
        128=>'搞笑视频',
        144=>'宝宝',
        140=>'萌宠',
        160=>'牛人视频',
        148=>'体育',
        28=>'美食',
        168=>'旅行',
        156=>'美妆时尚',
        114=>'汽车',
    ];

    static $meiPaiCatArr = [
        13=>'搞笑视频',
        63=>'舞蹈',
        19=>'女神视频',
        5=>'涨姿势',
        62=>'唱歌',
        27=>'美妆时尚',
        18=>'宝宝',
        3=>'旅行',
        6=>'萌宠',
        31=>'男神',
        59=>'美食',
    ];
    
    static $yiDianCatArr = [
        3977527910=>'爆笑',
        3977527926=>'综艺范',
        100140102502=>'推荐',
        3977527942=>'微电影',
        3977527958=>'妹纸',
        3977527974=>'现场',
        3977527990=>'猎奇',
        3977528006=>'动物世界',
        3977528022=>'生活',
        3977528038=>'悦耳',
        3977528054=>'运动',
        3977528070=>'萌娃宠物',
    ];

    /**
     *
     * http://c.m.163.com/nc/video/Tlist/T1457069041911/0-10.html 搞笑
     * http://c.m.163.com/nc/video/Tlist/T1457069205071/0-10.html 新闻
     * http://c.m.163.com/nc/video/Tlist/T1457069261743/0-10.html 八卦
     * http://c.m.163.com/nc/video/Tlist/T1457069319264/0-10.html 猎奇
     * http://c.m.163.com/nc/video/Tlist/T1457069232830/0-10.html 萌物
     * http://c.m.163.com/nc/video/Tlist/T1457069080899/0-10.html 美女帅哥
     * http://c.m.163.com/nc/video/Tlist/T1457069346235/0-10.html 体育
     * http://c.m.163.com/nc/video/Tlist/T1457069387259/0-10.html 黑科技
     * http://c.m.163.com/nc/video/Tlist/T1457069475980/0-10.html 涨姿势
     * http://c.m.163.com/nc/video/Tlist/T1464751736259/0-10.html 音乐
     * http://c.m.163.com/nc/video/Tlist/T1457069446903/0-10.html 二次元
     * http://c.m.163.com/nc/video/Tlist/T1457069421892/0-10.html 军武
     * http://c.m.163.com/nc/video/Tlist/T1461563165622/0-10.html 全景
     * @param int $page
     * @param int $limit
     * @param string $cat
     */
    public function actionNetEase($page = 1,$limit = 10,  $cat = "")
    {

        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        $curl = new curl\Curl();
        try {
        foreach (self::$netEaseCatArr as $oneCat => $val) {
            if (!empty($cat) && $oneCat != $cat) {
                continue;
            }

            for ($i = 0; $i < $page; $i++) {
                $url = "http://c.m.163.com/nc/video/Tlist/{$oneCat}/" . ($limit * $i) . "-" . $limit . ".html";
                

                $response = $curl->get($url);
                $respJson = Json::decode($response, true);
                $vIds = [];
                foreach ($respJson[$oneCat] as $oneVideo) {
                    
                    $siteUrl = "http://3g.163.com/ntes/special/0034073A/wechat_article.html?spst=0&spss=newsapp&spsw=1&spsf=qq&videoid=" . $oneVideo["vid"] . "&token=null";
                    $createTime = strtotime($oneVideo['ptime']);
                    $digNum = $buryNum = 0;
                    $commentNum = 20;//@todo 缺失。小于20将被过滤，所以置为20，该字段只用于过滤，不入库
                    $mvVideo = $this->saveVideo(
                        'netease/' . $oneVideo["vid"],
                        $oneVideo["mp4_url"],
                        $siteUrl,
                        $oneVideo["title"],
                        $oneVideo["description"],
                        $oneVideo["cover"],
                        'netease',
                        $oneVideo["length"],
                        0,//暂缺
                        0,//暂缺
                        $oneVideo['m3u8_url'],
                        $digNum,
                        $buryNum,
                        $oneVideo['playCount'],
                        $commentNum,
                        $createTime,
                        $errors
                    );
                    if (!$mvVideo) {
                        if (!empty($errors)) {
                            $this->error($errors);
                        }
                        continue;
                    }

                    $keywordArr = [self::$netEaseCatArr[$oneCat]];

                    if (isset($oneVideo["topicName"])) {
                        $keywordArr[] = 'u_' . $oneVideo["topicName"];
                    }
                    $this->saveTag($keywordArr, $mvVideo->id, $errors);

                    $vIds[] = $mvVideo->id;
                    echo $oneVideo["title"] . " >>> Done\n";

                }
                $this->finishThread($task->id, 'netease', $url, 'netease/video/' . $oneCat, $vIds, $errors);
            }
            echo "Cat " . $oneCat . " > Done.\n";
        }
        } catch (Exception $e) {
            $errors['Exception'] = [$e->getMessage()];
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }
        $this->endTask($task->id, json_encode($errors));


    }

    /**
     * @param string $cat value: subv_voice, video
     * @param integer $page min_behot_time=0 取默认,max_behot_time=[lastone]分页
     */
    public function actionToutiao($page = 1, $cat = "")
    {

        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        $curl = new curl\Curl();
        try {
            foreach (self::$catArr as $oneCat => $val) {
                if (!empty($cat) && $oneCat != $cat) {
                    continue;
                }

                $lastHotTime = 0;
                for ($i = 0; $i < $page; $i++) {
                    if ($i == 0) {
                        $pageParam = 'min_behot_time=' . $lastHotTime;
                    } else {
                        if ($lastHotTime > 0) {
                            $pageParam = 'max_behot_time=' . $lastHotTime;
                        } else {
                            continue;
                        }
                    }
                    $devicePrefix = "http://ic.snssdk.com/api/news/feed/v38/?iid=4818730159&os_version=9.3.2&aid=13&device_id=15388100121&app_name=news_article&channel=App%20Store&device_platform=iphone&idfa=D3DD2A38-5E20-458C-A3DE-AC693C5CFBE6&vid=42B539FB-B5A6-4A8B-9212-228B1FD13307&openudid=4add9b51319923895e1c98447d94833689131208&device_type=iPhone%206&ab_feature=z1&ab_group=z1&idfv=42B539FB-B5A6-4A8B-9212-228B1FD13307&ssmix=a&version_code=5.5.8&resolution=750*1334&ab_client=a1,b1,b7,f1,f5,e1&ac=WIFI&LBS_status=authroize";
                    $url = $devicePrefix . "&category={$oneCat}&city=&concern_id=&count=20&detail=1&image=1&language=zh-Hans-CN&last_refresh_sub_entrance_interval=" . time() . "&loc_mode=1&" . $pageParam . "&refer=1&strict=0";
                    
                    $response = $curl->get($url);
                    $respJson = Json::decode($response, true);
                    $vIds = [];
                    foreach ($respJson['data'] as $idx => $oneData) {

                        $oneJson = json_decode($oneData['content'], true);
                        if (!isset($oneJson["video_id"])) {
                            continue;
                        }
                        $videoApiUrl = "http://i.snssdk.com/video/urls/1/toutiao/mp4/" . $oneJson["video_id"] . "?callback=tt__video__9vp4me";
                        $videoResp = $curl->get($videoApiUrl);
                        if (preg_match('/\(([^)]*)\)/', $videoResp, $matches)) {
                            $videoRespJson = Json::decode($matches[1], true);
                            if (Utility::command_exist("node")) {
                                $realVideoUrl = "";
                                foreach ($videoRespJson['data']['video_list'] as $vkey => $oneVideo) {
                                    exec("node " . __DIR__ . "/../tt_video.js '" . $oneVideo['main_url'] . "'", $output);
                                    $vUrl = array_shift($output);
                                    if (strstr($vUrl, 'Signature') === false || empty($realVideoUrl)) {
                                        $realVideoUrl = $vUrl;
                                    }
                                }
                                $createTime = $oneJson['publish_time'];
                                //尝试保存视频
                                $playNum = isset($oneJson['video_detail_info']) ? $oneJson['video_detail_info']['video_watch_count'] : 0;
                                $mvVideo = $this->saveVideo(
                                    'toutiao/' . $oneJson["video_id"],
                                    $realVideoUrl,
                                    $oneJson["display_url"],
                                    $oneJson["title"],
                                    $oneJson["abstract"],
                                    $oneJson["large_image_list"][0]['url'],
                                    'toutiao',
                                    $oneJson["video_duration"],
                                    $oneJson["middle_image"]['width'],
                                    $oneJson["middle_image"]['height'],
                                    '',//m3u8
                                    $oneJson['digg_count'],
                                    $oneJson['bury_count'],
                                    $playNum,
                                    $oneJson['comment_count'],
                                    $createTime,
                                    $errors
                                );
                                if (!$mvVideo) {
                                    if (!empty($errors)) {
                                        $this->error($errors);
                                    }
                                    continue;
                                }
                                
                                $keywordArr = [self::$catArr[$oneCat]];
                                if (isset($oneJson["media_info"])) {
                                    $keywordArr[] = 'u_' . $oneJson["media_info"]["name"];
                                }
                                if (!empty($oneJson["keywords"])) {
                                    $keywordArr = array_merge($keywordArr, explode(',', $oneJson["keywords"]));
                                }
                                
                                $this->saveTag($keywordArr, $mvVideo->id, $errors);

                            } else {
                                throw new \yii\base\Exception("Command `node` cannot found.");
                            }

                            $vIds[] = $mvVideo->id;

                            echo $oneJson["title"] . " >>> Done\n";
                            if ($idx == count($respJson["data"]) - 1) {
                                $lastHotTime = $oneJson['behot_time'];
                            }
                        }
                    }
                    echo "Page " . ($i + 1) . " > Done.\n";
                    $this->finishThread($task->id, 'toutiao', $url, 'toutiao/video/' . $oneCat, $vIds, $errors);
                }
                echo "Cat " . $oneCat . " > Done.\n";
            }
        } catch (Exception $e) {
            $errors['Exception'] = [$e->getMessage()];
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }

        $this->endTask($task->id, json_encode($errors));


    }


    public function actionMeiPai($page = 1, $category = 0)
    {
        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        $curl = new curl\Curl();
        try {
            if ($category == 0) {
                $categories = array_keys(self::$meiPaiCatArr);
            } else {
                $categories = [$category];
            }
            foreach ($categories as $cat) {
                for ($i = 0; $i < $page; $i++) {
                    $url = 'https://newapi.meipai.com/channels/feed_timeline.json?id='. $cat .'&type=1&feature=new&page='. ($i + 1) .'&language=zh-Hans&client_id=1089857302&device_id=867981022011467&version=5010&channel=setup&model=Nexus+6P&os=6.0.1&locale=1&version=5010&channel=setup&model=Nexus+6P&os=6.0.1&locale=1';
                    $response = $curl->get($url);
                    $result = Json::decode($response, true);
                    $vIds = [];
                    foreach ($result as $oneElem) {
                        if (!isset($oneElem['media'])) {
                            continue;
                        }
                        $title = $oneElem['recommend_caption'];
                        $oneElem = $oneElem['media'];
                        $picSizes = explode('*', $oneElem['pic_size']);
                        $createTime = $oneElem['created_at'];
                        $length = isset($oneElem['time']) ? $oneElem['time'] : 0;
                        $videoAr = $this->saveVideo(
                            'meipai/'. $oneElem['id'], 
                            $oneElem['video'],
                            $oneElem['url'],
                            $title,
                            $oneElem['caption'],
                            $oneElem['cover_pic'],
                            'meipai',
                            $length,
                            $picSizes[0], 
                            $picSizes[1],
                            '',
                            $oneElem['likes_count'],
                            0,//bury
                            0,//play
                            $oneElem['comments_count'],
                            $createTime,
                            $errors
                        );
                        if (!$videoAr) {
                           if (!empty($errors)) {
                               $this->error($errors);
                           }
                           continue;
                        }
                        $tags = [self::$meiPaiCatArr[$cat]];
                        if(isset($oneElem['user']) && !empty($oneElem['user']['screen_name'])){
                            $tags[] = 'u_' . $oneElem['user']['screen_name'];
                        }
                        $this->saveTag($tags, $videoAr->id, $errors);
                        $vIds[] = $videoAr->id;
                        echo "Video " . $title. " >>> Done.\n";
//                        QsCollectHelper::saveHistory($collectEventId, 'meipai', $oneElem['url'], 'meipai' . '_' . $oneElem['id'], 1, $resourceAr->id, Resource::TYPE_VIDEO, $resourceAr->getErrors());
                    }

                    $this->finishThread($task->id, 'meipai', $url, 'meipai/video/' . $cat, $vIds, $errors);
                    echo "Page " . ($i + 1) . " >> Done.\n";
                }
                echo "Cat " . $cat. " > Done.\n";

            }

        } catch (Exception $e) {
            $errors['Exception'] = $e->getMessage();
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }

        $this->endTask($task->id, json_encode($errors));

    }

    public function actionMiaoPai($page = 1, $category = 0)
    {
        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        try {
            $curl = new curl\Curl();

            if ($category == 0) {
                $categories = array_keys(self::$miaoPaiCatArr);
            } else {
                $categories = [$category];
            }
            foreach ($categories as $cat) {
                for ($i = 0; $i < $page; $i++) {
                    $url = 'http://www.miaopai.com/miaopai/index_api?cateid=' . $cat . '&per=20&page=' . $i;
                    $response = $curl->get($url);

                    $result = Json::decode($response, true);
                    $vIds = [];
                    foreach ($result['result'] as $one) {
                        if (isset($one['channel']['stream'])) {
                            $siteUrl = 'http://m.miaopai.com/show/channel/' . $one['channel']['scid'];
                            
                            $dig = intval(str_replace(",","",$one['channel']['stat']['lcnt']));
                            if(strstr($dig, '万')){
                            	$dig = $dig * 10000;
                            }
                            $bury = intval($one['channel']['stat']['hcnt']);
                            $play = $one['channel']['stat']['vcnt'];
                            $createTime = $one['channel']['ext']['finishTime'];
                            $videoAr = $this->saveVideo(
                                'miaopai/' . $one['channel']['scid'],
                                $one['channel']['stream']['base'],
                                $siteUrl,
                                $one['channel']['ext']['ft'],
                                $one['channel']['ext']['t'],
                                $one['channel']['pic']['base'] . $one['channel']['pic']['m'],
                                'miaopai',
                                $one['channel']['ext']['length'],
                                $one['channel']['ext']['w'],
                                $one['channel']['ext']['h'],
                                '',//m3u8
                                $dig,
                                $bury,
                                $play,
                                $one['channel']['stat']['ccnt'],
                                $createTime,
                                $errors
                            );
                            if (!$videoAr) {
                                if (!empty($errors)) {
                                    $this->error($errors);
                                }
                                continue;
                            }
                            $tags = isset($one['channel']['topicinfo']) ? $one['channel']['topicinfo'] : [];
                            $tags[] = self::$miaoPaiCatArr[$cat];
                            if(isset($one['channel']['ext']['owner']) && !empty($one['channel']['ext']['owner']['nick'])){
                            	$tags[] = 'u_' . $one['channel']['ext']['owner']['nick'];
                            }
                            $this->saveTag($tags, $videoAr->id, $errors);
                            $vIds[] = $videoAr->id;
                            echo "Video " . $one['channel']['ext']['ft']. " >>> Done.\n";
                        }
                    }
//                    exit;
                    $this->finishThread($task->id, 'miaopai', $url, 'miaopai/video/' . $cat, $vIds, $errors);
                    echo "Page " . ($i + 1) . " >> Done.\n";
                }
            }
        } catch (Exception $e) {
            $errors['Exception'] = $e->getMessage();
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }

        $this->endTask($task->id, json_encode($errors));


    }

    private function saveTag($keywords, $videoId, &$errors)
    {
        foreach($keywords as $keyword) {
            //首先判断是否符合要求
            //只能是汉字，字母，数字或_-
            if(!preg_match('/^[\x{4e00}-\x{9fa5}A-Z0-9-a-z_-]+$/u', $keyword)){
                continue;
            }
            //汉字超过7个过滤
            if(preg_match('/[\x{4e00}-\x{9fa5}]{8,}/u', $keyword)){
                continue;
            }
            
            $mvKeyword = MvKeyword::findOne(['name' => $keyword]);
            //判断是否是过滤
            if (!$mvKeyword) {
                $mvKeyword = new MvKeyword();
                $mvKeyword->name = $keyword;
                if (!$mvKeyword->save()) {
                    $errors = array_merge($errors, $mvKeyword->getErrors());
                    $this->error($errors);
                    continue;
                }
            }
            elseif($mvKeyword->is_filter == 1){
            	continue;
            }

            $keywordRel = MvVideoKeywordRel::findOne([
                'video_id' => $videoId,
                'keyword_id' => $mvKeyword->id,
            ]);
            if (!$keywordRel) {
                $keywordRel = new MvVideoKeywordRel();
                $keywordRel->video_id = $videoId;
                $keywordRel->keyword_id = $mvKeyword->id;
                if (!$keywordRel->save()) {
                    $errors = array_merge($errors, $keywordRel->getErrors());
                    $this->error($errors);
                    continue;
                }
            }
            if(substr($keyword, 0, 2) == 'u_'){
            	continue;
            }
            //增加tag
            $tag = MvTag::findOne($mvKeyword->tag_id);
            if(empty($tag)){
                $tag = MvTag::find()->where(['name'=>$mvKeyword->name])->one();
                if(empty($tag)){
                    //新增tag
                    $tag = new MvTag();
                    $tag->name = $mvKeyword->name;
                    if(!$tag->save()){
                        $errors = array_merge($errors, $tag->getErrors());
                    	$this->error($errors);
                    	continue;
                    }
                }
                $mvKeyword->tag_id = $tag->id;
                $mvKeyword->save();
            }
            $tagRel = MvVideoTagRel::findOne(['mv_tag_id'=>$tag->id, 'mv_video_id'=>$videoId]);
            if(empty($tagRel)){
                $tagRel = new MvVideoTagRel();
                $tagRel->mv_tag_id = $tag->id;
                $tagRel->mv_video_id = $videoId;
                if(!$tagRel->save()){
                	$errors = array_merge($errors, $tagRel->getErrors());
                	$this->error($errors);
                	continue;
                }
            }
        }
        return;
    }

    private function saveVideo($key, $url, $siteUrl, $title, $desc, $coverUrl, $site, $length = 0, $vWidth = 0, $vHeight = 0, $m3u8 = '', $dig = 0, $bury = 0, $playCount = 0, $commentCount = 0, $createTime, &$errors) {

        if ($commentCount < 20) {
            return false;
        }
        if (empty($url) || empty($siteUrl)) {
            return false;
        }
        $title = strip_tags($title);
        $desc = strip_tags($desc);

        $video = Video::findOne(['key' => $key]);
        if (!$video) {
            $video = new Video();
            $video->key = $key;
            $video->status = Video::STATUS_ACTIVE;
            $video->url = $url;
            $video->m3u8_url = $m3u8;
            $video->site_url = $siteUrl;
            $video->desc = $desc;
            $video->length = $length;
            $video->add_time = time();
            $video->pub_time = time();
            $siteRegexSetting = SiteRegexSetting::findOne(['site' => $site]);
            $video->regex_setting = !empty($siteRegexSetting) ? $siteRegexSetting->id : 0;
            $coverForm = new ImageForm();
            $coverForm->url = $coverUrl;
            $cover = $coverForm->save();
            $video->cover_img = empty($cover) ? 0 : $cover->id;
            $video->width = !empty($vWidth) ? $vWidth : (!empty($cover) ? $cover->width : 0);
            $video->height = !empty($vHeight) ? $vHeight : (!empty($cover) ? $cover->height : 0);

            if (!$video->save()) {
                $errors = array_merge($errors, $video->getErrors());
                $this->error($errors);
                return false;
            }

        }

        $mvVideo = MvVideo::findOne(['video_id' => $video->id]);
        if (!$mvVideo) {
            $mvVideo = new MvVideo();
            $mvVideo->video_id = $video->id;
            $mvVideo->status = MvVideo::STATUS_ACTIVE;
            $mvVideo->create_time = !empty($createTime) ? $createTime : time();
            $mvVideo->update_time = time();
            $mvVideo->desc = $desc;
            $mvVideo->title = $title;

            if (!$mvVideo->save()) {
                $errors = array_merge($errors, $mvVideo->getErrors());
                $this->error($errors);
                return false;
            }
        }


        $videoCount = MvVideoCount::findOne(['video_id' => $mvVideo->id]);
        if (!$videoCount) {
            $videoCount = new MvVideoCount();
            $videoCount->video_id = $mvVideo->id;
        }
        $videoCount->like = $dig;
        $videoCount->bury = $bury;
        $videoCount->played = $playCount;
        if (!$videoCount->save()) {
            $errors = array_merge($errors, $videoCount->getErrors());
            $this->error($errors);
            return false;
        }

        return $mvVideo;
    }
    
    public function actionYiDian($page = 1, $cat = 0){

        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        try{
            $host = 'http://124.243.203.100';
            $cookie = 'JSESSIONID=rAkNO220FNIyfp5dNAWCLQ';
            $header = array('Accept-Language: zh-cn', 'Connection: Keep-Alive', 'Cache-Control: no-cache', "Cookie:$cookie");
            
            $catMap = !empty($cat) ? [$cat=>isset(self::$yiDianCatArr[$cat]) ? self::$yiDianCatArr[$cat] : ''] : self::$yiDianCatArr;
            $curl = new curl\Curl();
            $curl->setOption(CURLOPT_HTTPHEADER, $header);
            foreach($catMap as $cid => $cat){
                $start = 0;
                $end = 50;
                for ($i = 0; $i < $page; $i++) {
                    $suffix = "/Website/channel/news-list-for-channel?platform=1&infinite=true&cstart={$start}&group_fromid=g184&cend={$end}";
                    $suffix .= "&appid=yidian&cv=3.6.8&distribution=zhushou.360.cn&refresh=1&channel_id={$cid}&fields=docid&fields=date&fields=image&fields=image_urls&fields=like&fields=source&fields=title&fields=url&fields=comment_count&fields=up&fields=down&version=020107&net=wifi";
                    
                    $url = $host . $suffix;
                    $response = $curl->get($url);

                    $result = Json::decode($response, true);
                    if(empty($result['result'])){
                        echo "$url error\n";
                        continue;
                    }
                    $vIds = [];
                    foreach ($result['result'] as $one) {
                        if(!isset($one['video_url'])){
                        	continue;
                        }
                        $commentNum = isset($one['comment_count']) ? $one['comment_count'] : 0;
                        $digNum = isset($one['up']) ? $one['up'] : 0;
                        $buryNum = $playNum = 0;
                        $createTime = isset($one['date']) ? strtotime($one['date']) : time();
                        $videoAr = $this->saveVideo(
                            'yidian/' . $one['itemid'],
                            $one['video_url'],
                            "http://www.yidianzixun.com/article/" . $one['itemid'],//$one['url'],
                            $one['title'],
                            $one['summary'],
                            $one['image'],
                            'yidian',
                            $one['duration'],
                            0,//暂缺
                            0,//暂缺
                            '',
                            $digNum,
                            $buryNum,
                            $playNum,
                            $commentNum,
                            $createTime,
                            $errors
                        );
                        if (!$videoAr) {
                            if (!empty($errors)) {
                                $this->error($errors);
                            }
                            continue;
                        }
                        $utag = isset($one['wemedia_info']) && !empty($one['wemedia_info']['name']) ? ['u_' . $one['wemedia_info']['name']] : [];
                        $vsct = isset($one['vsct_show']) ? $one['vsct_show'] : [];
                        $keyword = isset($one['keywords']) ? $one['keywords'] : [];
                        $tags = array_merge($utag, $vsct, $keyword);
                        if(!empty($cat)){
                            $tags[] = $cat;
                        }

                        $this->saveTag($tags, $videoAr->id, $errors);
                        $vIds[] = $videoAr->id;
                        echo "\tVideo " . $one['title']. " >>> Done.\n";
                    }
                    
                    $this->finishThread($task->id, 'yidian', $url, "yidian/video/{$cid}", $vIds, $errors);
                    echo "Page " . ($i + 1) . " >> Done.\n";
                    
                    $start += empty($start) ? 16 : 5;
                    $end += empty($i) ? -4 : 5;
                    
                }
            }
        }
        catch(\Exception $e){
            $errors['Exception'] = $e->getMessage();
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }
        
        $this->endTask($task->id, json_encode($errors));
    }

}