<?php
/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/8/29
 * Time: 17:37
 */

namespace console\controllers;


use backend\models\microvideo\MvTag;
use backend\models\microvideo\MvVideoTagRel;
use common\models\ImageForm;
use common\models\SiteRegexSetting;
use common\models\Video;
use linslin\yii2\curl\Curl;
use microvideo\models\MvKeyword;
use microvideo\models\MvVideo;
use microvideo\models\MvVideoCount;
use microvideo\models\MvVideoKeywordRel;

class YoutubeController extends BaseController
{
    public function actionSpider() {
        $url = "http://207.226.142.113/youtube.php?k=";
        $channelList = ['PLFgquLnL59akA2PflFpeQG9L01VFg90wS', 'PL8fVUTBmJhHJmpP7sLb9JfLtdwCmYX9xC'];
        foreach($channelList as $channel) {
            $curl = new Curl();
            $resp = $curl->get($url . $channel);
            $videoList = json_decode($resp, true);
//            var_dump($videoList);
//            exit;
            foreach($videoList['items'] as $video) {
                $cat = $videoList['cat'];
                $videoAr = $this->saveVideo("youtube/". $video['videoId'], 'http://207.226.142.113/youtube_' . $video['videoId'] . '.mp4', $video['url'], $video['title'], $video['description']
                    , $video['thumbnail'], "youtube");
                if (!$videoAr) {
                    continue;
                }
                $tags = [$cat];
                $this->saveTag($tags, $videoAr->id, $errors);
                echo "\tVideo " . $videoAr->id . " >>> Done.\n";

            }
        }

    }


    private function saveTag($keywords, $videoId, &$errors)
    {
        $keywordExist = MvVideoKeywordRel::findOne([
            'video_id' => $videoId,
        ]);
        if (!empty($keywordExist)) {
            return;
        }

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

    private function saveVideo($key, $url, $siteUrl, $title, $desc, $coverUrl, $site, $length = 0, $vWidth = 0, $vHeight = 0, $m3u8 = '', $like = 0, $bury = 0, $playCount = 0, $commentCount = 0, $createTime = "", &$errors = []) {

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
            $video->add_time = !empty($createTime) ? $createTime : time();
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
            $mvVideo->status = MvVideo::STATUS_DELETE;
            $mvVideo->create_time = !empty($createTime) ? $createTime : time();
            $mvVideo->update_time = time();
            $mvVideo->desc = $desc;
            $mvVideo->title = $title;
            $mvVideo->key =  $key;
            $mvVideo->source_url = $siteUrl;

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
        $videoCount->like = $like;
        $videoCount->bury = $bury;
        $videoCount->played = $playCount;
        if (!$videoCount->save()) {
            $errors = array_merge($errors, $videoCount->getErrors());
            $this->error($errors);
            return false;
        }

        return $mvVideo;
    }


}