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
use yii\console\Controller;
use linslin\yii2\curl;
use yii\helpers\Json;

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

        try {
        foreach (self::$netEaseCatArr as $oneCat => $val) {
            if (!empty($cat) && $oneCat != $cat) {
                continue;
            }

            for ($i = 0; $i < $page; $i++) {
                $url = "http://c.m.163.com/nc/video/Tlist/{$oneCat}/" . ($limit * $i) . "-" . $limit . ".html";
                $curl = new curl\Curl();

                $response = $curl->get($url);
                $respJson = Json::decode($response, true);
                $vIds = [];
                foreach ($respJson[$oneCat] as $oneVideo) {
                    $video = Video::findOne(['key' => 'netease/' . $oneVideo["vid"]]);
                    if (!$video) {
                        $video = new Video();
                        $video->key = 'netease/' . $oneVideo["vid"];
                        $video->status = Video::STATUS_ACTIVE;
                        $video->url = $oneVideo["mp4_url"];
                        $video->m3u8_url = $oneVideo["m3u8_url"];
                        $video->site_url = "http://3g.163.com/ntes/special/0034073A/wechat_article.html?spst=0&spss=newsapp&spsw=1&spsf=qq&videoid=" . $oneVideo["vid"] . "&token=null";
                        $video->desc = $oneVideo["topicDesc"];
                        $video->width = 0;
                        $video->height = 0;
                        $video->length = $oneVideo["length"];
                        $video->add_time = time();
                        $video->pub_time = time();

                        $coverForm = new ImageForm();
                        $coverForm->url = $oneVideo["cover"];
                        $cover = $coverForm->save();
                        $video->cover_img = empty($cover) ? 0 : $cover->id;

                        if (!$video->save()) {
                            $errors = array_merge($errors, $video->getErrors());
                            $this->error($errors);
                            continue;
                        }

                    }

                    $mvVideo = MvVideo::findOne(['video_id' => $video->id]);
                    if (!$mvVideo) {
                        $mvVideo = new MvVideo();
                        $mvVideo->key = 'netease/' . $oneVideo["vid"];
                        $mvVideo->video_id = $video->id;
                        $mvVideo->status = MvVideo::STATUS_ACTIVE;
                        $mvVideo->create_time = time();
                        $mvVideo->update_time = time();
                        $mvVideo->source_url = "http://3g.163.com/ntes/special/0034073A/wechat_article.html?spst=0&spss=newsapp&spsw=1&spsf=qq&videoid=" . $oneVideo["vid"] . "&token=null";
                        $mvVideo->desc = $oneVideo["description"];
                        $mvVideo->title = $oneVideo["title"];

                        if (!$mvVideo->save()) {
                            $errors = array_merge($errors, $mvVideo->getErrors());
                            $this->error($errors);
                            continue;
                        }
                    }

                    $keywordArr = [self::$netEaseCatArr[$oneCat]];
                    if (isset($oneVideo["topicName"])) {
                        $keywordArr[] = $oneVideo["topicName"];
                    }
                    foreach ($keywordArr as $keyword) {
                        $mvKeyword = MvKeyword::findOne(['name' => $keyword]);
                        if (!$mvKeyword) {
                            $mvKeyword = new MvKeyword();
                            $mvKeyword->name = $keyword;
                            if (!$mvKeyword->save()) {
                                $errors = array_merge($errors, $mvKeyword->getErrors());
                                $this->error($errors);
                                continue;
                            }
                        }

                        $keywordRel = MvVideoKeywordRel::findOne([
                            'video_id' => $mvVideo->id,
                            'keyword_id' => $mvKeyword->id,
                        ]);
                        if (!$keywordRel) {
                            $keywordRel = new MvVideoKeywordRel();
                            $keywordRel->video_id = $mvVideo->id;
                            $keywordRel->keyword_id = $mvKeyword->id;
                            if (!$keywordRel->save()) {
                                $errors = array_merge($errors, $keywordRel->getErrors());
                                $this->error($errors);
                                continue;
                            }
                        }
                    }

                    $videoCount = MvVideoCount::findOne(['video_id' => $mvVideo->id]);
                    if (!$videoCount) {
                        $videoCount = new MvVideoCount();
                        $videoCount->video_id = $mvVideo->id;
                    }
                    $videoCount->dig = 0;
                    $videoCount->like = 0;
                    $videoCount->bury = 0;
                    $videoCount->played = $oneVideo['playCount'];
                    if (!$videoCount->save()) {
                        $errors = array_merge($errors, $videoCount->getErrors());
                        $this->error($errors);
                        continue;
                    }
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
                    $curl = new curl\Curl();
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

                                $video = Video::findOne(['key' => 'toutiao/' . $oneJson["video_id"]]);
                                if (!$video) {
                                    $video = new Video();
                                    $video->key = 'toutiao/' . $oneJson["video_id"];
                                    $video->status = Video::STATUS_ACTIVE;
                                    $video->url = $realVideoUrl;
                                    $video->site_url = $oneJson["display_url"];
                                    $video->desc = $oneJson["title"];
                                    $video->width = $oneJson["middle_image"]['width'];
                                    $video->height = $oneJson["middle_image"]['height'];
                                    $video->length = $oneJson["video_duration"];
                                    $video->add_time = time();
                                    $video->pub_time = time();
                                    $video->regex_setting = 3;

                                    $coverForm = new ImageForm();
                                    $coverForm->url = $oneJson["large_image_list"][0]['url'];
                                    $icon = $coverForm->save();
                                    $video->cover_img = empty($icon) ? 0 : $icon->id;

                                    if (!$video->save()) {
                                        $errors = array_merge($errors, $video->getErrors());
                                        $this->error($errors);
                                        continue;
                                    }

                                }

                                $mvVideo = MvVideo::findOne(['video_id' => $video->id]);
                                if (!$mvVideo) {
                                    $mvVideo = new MvVideo();
                                    $mvVideo->key = 'toutiao/' . $oneJson["video_id"];
                                    $mvVideo->video_id = $video->id;
                                    $mvVideo->status = MvVideo::STATUS_ACTIVE;
                                    $mvVideo->create_time = time();
                                    $mvVideo->update_time = time();
                                    $mvVideo->source_url = $oneJson["display_url"];
                                    $mvVideo->desc = $oneJson["abstract"];
                                    $mvVideo->title = $oneJson["title"];

                                    if (!$mvVideo->save()) {
                                        $errors = array_merge($errors, $mvVideo->getErrors());
                                        $this->error($errors);
                                        continue;

                                    }
                                }

                                $keywordArr = [self::$catArr[$oneCat]];
                                if (isset($oneJson["media_info"])) {
                                    $keywordArr[] = $oneJson["media_info"]["name"];
                                }
                                if (!empty($oneJson["keywords"])) {
                                    $keywordArr[] = array_merge($keywordArr, explode(',', $oneJson["keywords"]));
                                        foreach ($keywordArr as $keyword) {
                                            $mvKeyword = MvKeyword::findOne(['name' => $keyword]);
                                            if (!$mvKeyword) {
                                                $mvKeyword = new MvKeyword();
                                                $mvKeyword->name = $keyword;
                                                if (!$mvKeyword->save()) {
                                                    $errors = array_merge($errors, $mvKeyword->getErrors());
                                                    $this->error($errors);
                                                    continue;
                                                }
                                            }

                                            $keywordRel = MvVideoKeywordRel::findOne([
                                                'video_id' => $mvVideo->id,
                                                'keyword_id' => $mvKeyword->id,
                                            ]);
                                            if (!$keywordRel) {
                                                $keywordRel = new MvVideoKeywordRel();
                                                $keywordRel->video_id = $mvVideo->id;
                                                $keywordRel->keyword_id = $mvKeyword->id;
                                                if (!$keywordRel->save()) {
                                                    $errors = array_merge($errors, $keywordRel->getErrors());
                                                    $this->error($errors);
                                                    continue;
                                                }
                                            }
                                        }
                                }

                                $videoCount = MvVideoCount::findOne(['video_id' => $mvVideo->id]);
                                if (!$videoCount) {
                                    $videoCount = new MvVideoCount();
                                    $videoCount->video_id = $mvVideo->id;
                                }
                                $videoCount->dig = $oneJson['digg_count'];
                                $videoCount->like = $oneJson['like_count'];
                                $videoCount->bury = $oneJson['bury_count'];
                                $videoCount->played = 0;
                                if (!$videoCount->save()) {
                                    $errors = array_merge($errors, $videoCount->getErrors());
                                    $this->error($errors);
                                    continue;
                                }


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
                        //$key, $url, $siteUrl, $title, $desc, $coverUrl, $site, $length = 0, $vWidth = 0, $vHeight = 0, $dig = 0, $commentCount = 0, &$errors
                        $videoAr = $this->saveVideo('meipai/'. $oneElem['id'], $oneElem['video'], $oneElem['url'], $title, $oneElem['caption'],
                            $oneElem['cover_pic'], 'meipai',
                            isset($oneElem['time']) ? $oneElem['time'] : 0,
                            $picSizes[0], $picSizes[1], $oneElem['likes_count'],$oneElem['comments_count'], $errors);
                       if (!$videoAr) {
                           if (!empty($errors)) {
                               $this->error($errors);
                           }
                            continue;
                        }

                        $this->saveTag([self::$meiPaiCatArr[$cat]], $videoAr->id, $errors);
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
                            $videoAr = $this->saveVideo(
                                'miaopai/' . $one['channel']['scid'],
                                $one['channel']['stream']['base'],
                                $siteUrl,
                                $one['channel']['ext']['t'],
                                $one['channel']['ext']['ft'],
                                $one['channel']['pic']['base'] . $one['channel']['pic']['m'],
                                'miaopai',
                                $one['channel']['ext']['length'],
                                $one['channel']['ext']['w'],
                                $one['channel']['ext']['h'],
                                $dig,
                                $one['channel']['stat']['ccnt'],
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

    private function saveTag($tags, $videoId, &$errors)
    {
        foreach($tags as $tag) {
            $mvKeyword = MvKeyword::findOne(['name' => $tag]);
            if (!$mvKeyword) {
                $mvKeyword = new MvKeyword();
                $mvKeyword->name = $tag;
                if (!$mvKeyword->save()) {
                    $errors = array_merge($errors, $mvKeyword->getErrors());
                    $this->error($errors);
                    continue;
                }
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
        }
        return;
    }

    private function saveVideo($key, $url, $siteUrl, $title, $desc, $coverUrl, $site, $length = 0, $vWidth = 0, $vHeight = 0, $dig = 0, $commentCount = 0, &$errors) {

        if ($commentCount < 20) {
            return false;
        }
        if (empty($url) || empty($siteUrl)) {
            return false;
        }

        $video = Video::findOne(['key' => $key]);
        if (!$video) {
            $video = new Video();
            $video->key = $key;
            $video->status = Video::STATUS_ACTIVE;
            $video->url = $url;
            $video->site_url = $siteUrl;
            $video->desc = $desc;
            $video->width = $vWidth;
            $video->height = $vHeight;
            $video->length = $length;
            $video->add_time = time();
            $video->pub_time = time();
            $siteRegexSetting = SiteRegexSetting::findOne(['site' => $site]);
            $video->regex_setting = !empty($siteRegexSetting) ? $siteRegexSetting->id : 0;
            $coverForm = new ImageForm();
            $coverForm->url = $coverUrl;
            $cover = $coverForm->save();
            $video->cover_img = empty($cover) ? 0 : $cover->id;

            if (!$video->save()) {
                $errors = array_merge($errors, $video->getErrors());
                $this->error($errors);
                return false;
            }

        }

        $mvVideo = MvVideo::findOne(['video_id' => $video->id]);
        if (!$mvVideo) {
            $mvVideo = new MvVideo();
            $mvVideo->key = $key;
            $mvVideo->video_id = $video->id;
            $mvVideo->status = MvVideo::STATUS_ACTIVE;
            $mvVideo->create_time = time();
            $mvVideo->update_time = time();
            $mvVideo->source_url = $siteUrl;
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
        $videoCount->dig = $dig;
        $videoCount->like = 0;
        $videoCount->bury = 0;
        $videoCount->played = 0;
        if (!$videoCount->save()) {
            $errors = array_merge($errors, $videoCount->getErrors());
            $this->error($errors);
            return false;
        }

        return $mvVideo;
    }

}