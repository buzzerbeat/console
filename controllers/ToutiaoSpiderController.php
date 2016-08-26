<?php
/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/8/8
 * Time: 12:30
 */

namespace console\controllers;


use article\models\NewsItem;
use article\models\TtArticle;
use article\models\TtArticleCount;
use article\models\TtArticleImage;
use article\models\TtArticleTag;
use article\models\TtArticleTagRel;
use article\models\TtArticleVideo;
use article\models\TtComment;
use article\models\TtMedia;
use common\components\Utility;
use common\models\Article;
use common\models\Comment;
use common\models\ImageForm;
use common\models\SiteRegexSetting;
use common\models\User;
use common\models\Video;
use linslin\yii2\curl\Curl;
use yii\console\Controller;
use yii\helpers\Json;

class ToutiaoSpiderController extends BaseController
{
    //https://lf.snssdk.com/api/news/feed/v40/?category=video_olympic&refer=1&count=20&last_refresh_sub_entrance_interval=1470630532&bd_city=%E5%8C%97%E4%BA%AC%E5%B8%82&bd_latitude=40.030608&bd_longitude=116.346132&bd_loc_time=1470630247&loc_mode=7&loc_time=1470630228&latitude=40.0280175&longitude=116.3412354&city=%E6%B5%B7%E6%B7%80%E5%8C%BA&lac=41035&cid=6011997&cp=537da18d00a84q1&iid=5085867736&device_id=24279200404&ac=wifi&channel=wap_feeds_test1&aid=13&app_name=news_article&version_code=573&version_name=5.7.3&device_platform=android&ab_version=concern_talk_data_test8_01%2Ctop_search_bar_new_user_0801_test%2Ctab_config1_573_new_android_user_base1%2Cgroup_favor_no_need_login_v572%2Cnew_user_interest_guide_20160803_base2&ab_client=a1%2Cc4%2Ce1%2Cf2%2Cg2%2Cf7&ab_feature=z1&abflag=3&ssmix=a&device_type=Nexus+6P&device_brand=google&language=zh&os_api=23&os_version=6.0.1&uuid=867981022011467&openudid=a47ce2f29995e22a&manifest_version_code=573&resolution=1440*2392&dpi=560&update_version_code=5734&_rticket=1470630532473
    public function actionNews()
    {

        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        try {
            $prefix = 'news_';
            $cats = ['society', 'tech', 'entertainment', 'car', 'sports', 'finance', 'game', 'military', 'world' ,'story'];
            foreach ($cats as $cat) {
                $url = "https://lf.snssdk.com/api/news/feed/v40/?category=" . $prefix . $cat . "&concern_id=6215497899397089794&refer=1&count=20&min_behot_time=1470797978&last_refresh_sub_entrance_interval=1470988580&bd_city=%E5%8C%97%E4%BA%AC%E5%B8%82&bd_latitude=40.030587&bd_longitude=116.346081&bd_loc_time=1470988504&loc_mode=7&loc_time=1470988487&latitude=40.02768&longitude=116.340181&city=%E6%B5%B7%E6%B7%80%E5%8C%BA&lac=41035&cid=6011997&cp=5674a6d88c124q1&iid=5086033848&device_id=3088901026&ac=wifi&channel=growth_wap&aid=13&app_name=news_article&version_code=573&version_name=5.7.3&device_platform=android&ab_version=concern_talk_data_test10_09%2Cgroup_favor_optional_login_v572%2Ctab_config1_573_old_android_user_change2&ab_client=a1%2Cc1%2Ce1%2Cf2%2Cg2%2Cf7&ab_feature=z1&abflag=7&ssmix=a&device_type=Nexus+5&device_brand=google&language=zh&os_api=23&os_version=6.0.1&uuid=352136067342168&openudid=f9de7d976f67b82f&manifest_version_code=573&resolution=1080*1776&dpi=480&update_version_code=5734&_rticket=1470988580312";

                $curl = new Curl();
                $curl->setOption(CURLOPT_HTTPHEADER, [
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
                    "Upgrade-Insecure-Requests: 1",
                    "Host: lf.snssdk.com",
                    "Cookie:uuid=\"w:af1c80b1201640079ec769757cd9b5e7\"; tt_webid=20220733492",
                    "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                ]);
                 $newsResp = $curl->get($url);
                file_put_contents("/tmp/news_data_". date('YmdHis'), $newsResp);

                $newsList = json_decode($newsResp, true);
                $artIds = [];
                $filter = 0;
                $duplicate = 0;
                $success = 0;
                $fail = 0;
                $total = count($newsList['data']);
                foreach ($newsList['data'] as $idx => $news) {
                    $content = json_decode($news['content'], true);
                    if (isset($content['is_stick']) && $content['is_stick'] == 1) {
                        //不采集置顶文章
                        $filter ++;
                        continue;

                    }
                    if (empty($content['item_id'])) {

                        continue;
                    }

                    if (isset($content['is_subject']) && $content['is_subject']) {
                        //不采集专题
                        $filter ++;
                        continue;
                    }

                    if (isset($content['ad_id'])) {
                        //不采集广告
                        $filter ++;
                        continue;
                    }
                    $article = Article::findOne(['key' => 'toutiao/' . $content['item_id']]);
                    if (empty($article)) {
                        $article = new Article();
                        $article->key = 'toutiao/' . $content['item_id'];
                        $article->title = $content['title'];
                        $article->abstract = $content['abstract'];
                        $article->src_link = $content['article_url'];
                        $article->pub_time = $content['publish_time'];


                        if (!$article->save()) {
                            $errors = array_merge($errors, $article->getErrors());
                            $fail ++;
                            continue;
                        }
//                        $success ++;
                    } else {
                        $duplicate ++;
                    }


                    $ttArticle = TtArticle::findOne(['article_id' => $article->id]);
                    $ttVideo = false;
                    if (empty($ttArticle)) {
                        $ttArticle = new TtArticle();
                        $ttArticle->article_id = $article->id;
                        if ($content['has_video']) {
                            //video
                            $ttArticle->type = TtArticle::TYPE_VIDEO;
                            if (isset($content['video_detail_info']) && isset($content['video_detail_info']["video_id"])) {
                                $videoApiUrl = "http://i.snssdk.com/video/urls/1/toutiao/mp4/" . $content['video_detail_info']["video_id"] . "?callback=tt__video__9vp4me";
                                echo $videoApiUrl . "\n";
                                $curl->setOption(CURLOPT_HTTPHEADER, [
                                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36",
                                    "Upgrade-Insecure-Requests: 1",
                                    "Host:i.snssdk.com",
                                    "Cookie:uuid=\"w:af1c80b1201640079ec769757cd9b5e7\"; tt_webid=20220733492",
                                    "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                                    "Accept-Encoding:gzip, deflate, sdch, br",
                                ]);
                                $videoResp = gzdecode($curl->get($videoApiUrl));
                                if (preg_match('/\(([^)]*)\)/', $videoResp, $matches)) {
                                    $errors = [];
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
                                        echo $realVideoUrl . " Save Video\n";
                                        $createTime = $content['publish_time'];
                                        //尝试保存视频
                                        $playNum = isset($content['video_detail_info']) ? $content['video_detail_info']['video_watch_count'] : 0;
                                        $ttVideo = $this->saveVideo(
                                            'toutiao/' . $content['video_detail_info']["video_id"],
                                            $realVideoUrl,
                                            $content["display_url"],
                                            $content["title"],
                                            $content["abstract"],
                                            !empty($content["large_image_list"]) ? $content["large_image_list"][0]['url'] : "",
                                            'toutiao',
                                            $content["video_duration"],
                                            $content["middle_image"]['width'],
                                            $content["middle_image"]['height'],
                                            '',//m3u8
                                            $content['digg_count'],
                                            $content['bury_count'],
                                            $playNum,
                                            $content['comment_count'],
                                            $createTime,
                                            $errors
                                        );

                                    }
                                }
                            } else {
                                $fail ++;
                                continue;
                            }
//
                        } else {
                            if (isset($content['gallary_style'])) {
                                $ttArticle->type = TtArticle::TYPE_GALLERY;
                            } else {
                                $ttArticle->type = TtArticle::TYPE_ARTICLE;
                            }

                        }

                        $style = 0;
                        $coverList = [];
                        if (!empty($content['image_list'])) {
                            if (count($content['image_list']) >= 3) {
                                $style = TtArticle::STYLE_MULTI_THUMBS;
                            }
                            foreach ($content['image_list'] as $coverImg) {
                                $coverList[] = [
                                    "uri" => $coverImg['uri'],
                                    "url" => $coverImg['url'],
                                ];
                            }


                        } else if (!empty($content["large_image_list"])) {
                            $style = TtArticle::STYLE_LARGE_THUMB;
                            $largeImage = array_shift($content['large_image_list']);
                            $coverList[] = [
                                "uri" => $largeImage['uri'],
                                "url" => $largeImage['url'],
                            ];

                        } else if (!empty($content["middle_image"])) {
                            $style = TtArticle::STYLE_THUMB;
                            $coverList[] = [
                                "uri" => $content['middle_image']['uri'],
                                "url" => $content['middle_image']['url'],
                            ];
                        } else {
                            $style = TtArticle::STYLE_NO_THUMB;
                        }

                        $coverIds = [];
                        foreach ($coverList as $idx => $coverItem) {
                            $ttCover = TtArticleImage::findOne([
                                'tt_uri' => $coverItem['uri'],
                            ]);

                            if (empty($ttCover)) {

                                $coverUrl = $coverItem['url'];
                                if (strstr($coverUrl, 'webp')) {
                                    $coverUrl = $this->convertWebpUrlToJpeg($coverItem['uri']);
                                }

                                $gImageForm = new ImageForm();
                                $gImageForm->url = $coverUrl;
                                $gImage = $gImageForm->ttSave();

                                if (!empty($gImage) && $gImage->id > 0) {
                                    $ttCover = TtArticleImage::findOne($gImage->id);
                                    if (empty($ttCover)) {
                                        $ttCover = new TtArticleImage();
                                        $ttCover->image_id = $gImage->id;
                                        $ttCover->tt_article_id = $ttArticle->article_id;
                                        $ttCover->index = $idx;
                                        $ttCover->tt_uri = str_replace('list', 'large', $coverItem['uri']);
                                        $ttCover->mode = TtArticleImage::MODE_DEFAULT;
                                        $ttCover->is_thumb = 1;
                                        if (!$ttCover->save()) {
                                            $errors = array_merge($errors, $ttCover->getErrors());
                                            $fail ++;
                                            continue;
                                        }
                                    }
                                } else {
                                    $errors = array_merge($errors, $gImageForm->getErrors());
                                }

                            }
                            if (!empty($ttCover->image_id)) {
                                $coverIds[] = $ttCover->image_id;
                            }
                        }
                        if (!empty($coverIds)) {
                            $ttArticle->cover_ids = implode(',', $coverIds);
                        }

                        if (!empty($coverIds)) {
                            $article->cover = $coverIds[0];
                        }

                        if (!$article->save()) {
                            $errors = array_merge($errors, $article->getErrors());
                            $fail ++;
                            continue;
                        }

                        $ttArticle->style = $style;

                        if (isset($content['keywords']) && !empty($content['keywords'])) {
                            $keywords = explode(',', $content['keywords']);
                            foreach ($keywords as $keyword) {
                                $ttTag = TtArticleTag::findOne(['name' => $keyword]);
                                if (empty($ttTag)) {
                                    $ttTag = new TtArticleTag();
                                    $ttTag->name = $keyword;
                                    if (!$ttTag->save()) {
                                        $errors = array_merge($errors, $ttTag->getErrors());
                                        $fail ++;
                                        continue;
                                    }

                                }
                                $ttTagRel = TtArticleTagRel::findOne([
                                    'tag_id' => $ttTag->id,
                                    'article_id' => $article->id,
                                ]);
                                if (empty($ttTagRel)) {
                                    $ttTagRel = new TtArticleTagRel();
                                    $ttTagRel->article_id = $article->id;
                                    $ttTagRel->tag_id = $ttTag->id;
                                    if (!$ttTagRel->save()) {
                                        $errors = array_merge($errors, $ttTagRel->getErrors());
                                        $fail ++;
                                        continue;

                                    }
                                }
                            }
                        }

                        $ttArticleCount = TtArticleCount::findOne(['article_id' => $article->id]);
                        if (empty($ttArticleCount)) {
                            $ttArticleCount = new TtArticleCount();
                            $ttArticleCount->article_id = $article->id;
                            $ttArticleCount->like_count = isset($content['like_count']) ? $content['like_count'] : 0;
                            $ttArticleCount->dig_count = isset($content['digg_count']) ? $content['digg_count'] : 0;
                            $ttArticleCount->comment_count = isset($content['comment_count']) ? $content['comment_count'] : 0;
                            $ttArticleCount->bury_count = isset($content['bury_count']) ? $content['comment_count'] : 0;
                            $ttArticleCount->read_count = isset($content['read_count']) ? $content['read_count'] : 0;
                            if (!$ttArticleCount->save()) {
                                $errors = array_merge($errors, $ttArticleCount->getErrors());
                                $fail ++;
                                continue;
                            }
                        }

                        if (!$ttArticle->save()) {
                            $errors = array_merge($errors, $ttArticle->getErrors());
                            $fail ++;
                            continue;
                        }

                        if (!empty($ttVideo)) {
                            $ttVideo->article_id = $ttArticle->article_id;
                            if (!$ttVideo->save()) {
                                $errors = array_merge($errors, $ttVideo->getErrors());
                                $fail ++;
                                continue;
                            }
                        }
                    }

                    $detailUrl = "https://a3.bytecdn.cn/article/content/13/1/" . $content['tag_id'] . "/" . $content['item_id'] . "/1/";
                    echo $detailUrl . "\n";
                    $curl->setOption(CURLOPT_HTTPHEADER, [
                        "User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0.1; Nexus 5 Build/MMB29Q) NewsArticle/5.7.3 okhttp/2.6.3",
                        "Host: a3.bytecdn.cn",
                    ]);
                    $newsDetail = $curl->get($detailUrl);
                    $newsDetailArr = json_decode($newsDetail, true);
                    $newsDetailData = $newsDetailArr['data'];
                    if (!empty($article)) {

                        $article->content = $newsDetailData['content'];
                        if (!$article->save()) {
                            $errors = array_merge($errors, $article->getErrors());
                            $fail ++;
                            continue;
                        }

                        if (isset($newsDetailData['h5_extra'])) {
                            if (!empty($newsDetailData['h5_extra']['media'])) {
                                $mediaArr = $newsDetailData['h5_extra']['media'];
                                $media = TtMedia::findOne(['tt_media_id' => $mediaArr['id']]);
                                if (empty($media)) {
                                    $media = new TtMedia();
                                    $media->tt_media_id = $mediaArr['id'];
                                    $media->name = $mediaArr['name'];
                                    $coverForm = new ImageForm();
                                    $coverForm->url = $mediaArr['avatar_url'];
                                    $cover = $coverForm->ttSave();
                                    $media->avatar = empty($cover) ? 0 : $cover->id;
                                    $media->description = $mediaArr['description'];
                                    if (!$media->save()) {
                                        $errors = array_merge($errors, $media->getErrors());
                                        $fail ++;
                                        continue;
                                    }
                                }
                            }
                        }
                        if (!empty($media)) {
                            $ttArticle->media_id = $media->id;
                            if (!$ttArticle->save()) {
                                $errors = array_merge($errors, $ttArticle->getErrors());
                                $fail ++;
                                continue;
                            }
                        }

                        $newsItem = NewsItem::findOne(['type' => $ttArticle->type, 'relation_id' => $ttArticle->article_id]);
                        if (empty($newsItem)) {
                            $newsItem = new NewsItem();
                            if (!$newsItem->recommend($ttArticle)) {
                                $errors = array_merge($errors, $newsItem->getErrors());
                                $fail ++;
                                continue;
                            }
                        }

                        if ($ttArticle->type == TtArticle::TYPE_ARTICLE) {
                            if (isset($newsDetailData['image_detail'])) {
                                foreach ($newsDetailData['image_detail'] as $idx => $imgDetail) {
                                    $ttImage = TtArticleImage::findOne([
                                        'tt_article_id' => $ttArticle->article_id,
                                        'tt_uri' => $imgDetail['uri'],
                                    ]);
                                    if (empty($ttImage)) {
                                        $gImageForm = new ImageForm();
                                        $gImageForm->url = $imgDetail['url'];
                                        $gImage = $gImageForm->ttSave();
                                        if (!empty($gImage) && $gImage->id > 0) {
                                            $ttImage = TtArticleImage::findOne($gImage->id);
                                            if (empty($ttImage)) {
                                                $ttImage = new TtArticleImage();
                                                $ttImage->image_id = $gImage->id;
                                                $ttImage->tt_article_id = $ttArticle->article_id;
                                                $ttImage->index = $idx;
                                                $ttImage->tt_uri = $imgDetail['uri'];
                                                $ttImage->mode = TtArticleImage::MODE_DEFAULT;
                                                $ttImage->is_thumb = 0;
                                                if (!$ttImage->save()) {
                                                    $errors = array_merge($errors, $ttImage->getErrors());
                                                    $fail ++;
                                                    continue;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        } else if ($ttArticle->type == TtArticle::TYPE_GALLERY) {
                            if (isset($newsDetailData['gallery'])) {
                                foreach ($newsDetailData['gallery'] as $idx => $gallery) {
                                    $ttImage = TtArticleImage::findOne([
                                        'tt_article_id' => $ttArticle->article_id,
                                        'tt_uri' => $gallery['sub_image']['uri'],
                                    ]);
                                    if (empty($ttImage)) {
                                        $gImageForm = new ImageForm();
                                        $gImageForm->url = $gallery['sub_image']['url'];
                                        $gImage = $gImageForm->ttSave();
                                        if (!empty($gImage) && $gImage->id > 0) {
                                            $ttImage = TtArticleImage::findOne($gImage->id);
                                            if (empty($ttImage)) {
                                                $ttImage = new TtArticleImage();
                                                $ttImage->image_id = $gImage->id;
                                                $ttImage->tt_article_id = $ttArticle->article_id;
                                                $ttImage->index = $idx;
                                                $ttImage->tt_uri = $gallery['sub_image']['uri'];
                                                $ttImage->sub_title = $gallery['sub_title'];
                                                $ttImage->sub_abstract = $gallery['sub_abstract'];
                                                $ttImage->mode = TtArticleImage::MODE_GALLERY;
                                                $ttImage->is_thumb = 0;
                                                if (!$ttImage->save()) {
                                                    $errors = array_merge($errors, $ttImage->getErrors());
                                                    $fail ++;
                                                    continue;
                                                }
                                            }
                                        }

                                    }

                                }
                            }

                        }

                        $commentUrl = "http://isub.snssdk.com/article/v1/tab_comments/?group_id=" . $content['group_id'] . "&item_id=" . $content['item_id'] . "&aggr_type=1&count=20&offset=0";
                        echo $commentUrl . "\n";
                        $curl->setOption(CURLOPT_HTTPHEADER, [
                            "User-Agent: Dalvik/2.1.0 (Linux; U; Android 6.0.1; Nexus 5 Build/MMB29Q) NewsArticle/5.7.3 okhttp/2.6.3",
                            "Host: isub.snssdk.com",
                        ]);
                        $commentResp = $curl->get($commentUrl);
                        $commentJson = json_decode($commentResp, true);
                        $commentArr = $commentJson['data'];
                        foreach ($commentArr as $oneComment) {
                            if (!empty($oneComment['comment']['reply_list'])) {
                                continue;
                            }
                            $ttComment = TtComment::findOne(['key' => $oneComment['comment']['id']]);
                            if (empty($ttComment)) {
                                $comment = new Comment();
                                $comment->content = $oneComment['comment']['text'];
                                $comment->dig = $oneComment['comment']['digg_count'];
                                $comment->create_time = $oneComment['comment']['create_time'];
                                $comment->item_id = $article->id;
                                $comment->item_type = "article/article";
                                $comment->status = Comment::STATUS_ACTIVE;
                                $comment->user_id = User::genRobotUser($oneComment['comment']['user_name'], $oneComment['comment']['user_profile_image_url'], "article");
                                if (!$comment->save()) {
                                    $errors = array_merge($errors, $comment->getErrors());
                                    continue;
                                }
                                $ttComment = new TtComment();
                                $ttComment->comment_id = $comment->id;
                                $ttComment->key = strval($oneComment['comment']['id']);
                                if (!$ttComment->save()) {
                                    $errors = array_merge($errors, $ttComment->getErrors());
                                    continue;
                                }
                            }
                        }
                        $artIds[] = $article->id;
                        $success ++;
                    }

                }
                $this->finishThread($task->id, 'toutiao', $url, "toutiao/article/{$cat}", $artIds, $errors, $total, $success, $fail, $duplicate, $filter);
//                echo "Page " . ($idx + 1) . " >> Done.\n";
            }

        } catch (\Exception $e) {
            $errors['Exception'] = $e->getMessage();
            $this->error($errors);
            $this->endTask($task->id, json_encode($errors));
            exit(-1);
        }

        $this->endTask($task->id, json_encode($errors));

    }

    private function saveVideo($key, $url, $siteUrl, $title, $desc, $coverUrl, $site, $length = 0, $vWidth = 0, $vHeight = 0, $m3u8 = '', $dig = 0, $bury = 0, $playCount = 0, $commentCount = 0, $createTime, &$errors)
    {

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

        $ids = explode('/', $key);
        $ttVideo = TtArticleVideo::findOne(['video_id' => $video->id]);
        if (!$ttVideo) {
            $ttVideo = new TtArticleVideo();
            $ttVideo->video_id = $video->id;
            $ttVideo->tt_video_id = $ids[1];
            $ttVideo->create_time = !empty($createTime) ? $createTime : time();

            if (!$ttVideo->save()) {
                $errors = array_merge($errors, $ttVideo->getErrors());
                $this->error($errors);
                return false;
            }
        }


        return $ttVideo;
    }

    private function convertWebpUrlToJpeg($uri)
    {
        $uriArr = explode('/', $uri);
        if (count($uriArr) > 1) {
            return "http://p3.pstatp.com/large/" . $uriArr[1];
        }
        return false;
    }

    public function actionTestOutput($test = "") {
        $resp = file_get_contents('/tmp/news_data_20160824094021');
        $newsList = json_decode($resp, true);
        foreach ($newsList['data'] as $idx => $news) {
            $content = json_decode($news['content'], true);
            if (empty($content['item_id'])) {
                continue;
            }
            if (!empty($test) && $test == $content['item_id']) {
                var_dump($content);
            }


        }

    }


}
