<?php

namespace console\controllers;
use common\models\Image;
use common\models\ImageForm;
use wallpaper\models\AlbumImgRel;
use wallpaper\models\Album;
use wallpaper\models\Category;
use wallpaper\models\WpImage;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use linslin\yii2\curl;
/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/7/6
 * Time: 16:46
 */



class SpiderController extends BaseController
{
    public function actionTest()
    {
//       echo  urldecode("CrawlThreadSearch%5Bid%5D=&CrawlThreadSearch%5Bkey%5D=&CrawlThreadSearch%5Btask_id%5D=3&CrawlThreadSearch%5Bstatus%5D=&CrawlThreadSearch%5Btime%5D=");
//       echo  urldecode("CrawlThreadSearch%5Bid%5D%3D%26CrawlThreadSearch%5Bkey%5D%3D%26CrawlThreadSearch%5Btask_id%5D%3D3%26CrawlThreadSearch%5Bstatus%5D%3D%26CrawlThreadSearch%5Btime%5D%3D");
    }
    public function actionCat($catNum = 0, $perLimit = 20, $page = 1) {
        $task = $this->createTask();
        if (!$task) {
            print("任务创建失败");
            exit(-1);
        }
        $errors = [];
        try {
            $suffix = "&screen_w=750&screen_h=1334&ir=0&app=9P_iPhone5Wallpapers&v=2.8&lang=zh-Hans-CN&"
                . "it=1467781106.738783&ots=5&jb=0&as=0&mobclix=0&deviceid=replaceudid&macaddr=&idv=5492D869-782A-4219-A723-2CB678E66738"
                . "&idvs=&ida=D3DD2A38-5E20-458C-A3DE-AC693C5CFBE6&phonetype=iphone&model=iphone7%2C2&osn=iPhone%20OS&osv=9.3.2&tz=8";
            $curl = new curl\Curl();
            $url = "http://page.appdao.com/page?name=sw_collection_page" . $suffix;
			echo $url;
			throw new exception(111);
            $response = $curl->get($url);
            $catList = Json::decode($response, true);
            $catCount = 0;
            foreach($catList['data'] as $cat) {
                if (isset($cat['extra'])) {
                    foreach($cat['data'] as $child) {
                        $catCount ++;
                        if ($catNum > 0 && $catCount > $catNum) {
                            $this->endTask($task->id, json_encode($errors));
                            exit(0);
                        }
                        if (isset($child['tag'])) {
                            echo $child['tag'] . " >>> " . $child['title'] . "\n";
                            $album = Album::findOne(['key' => $child['tag']]);
                            if (empty($album)) {
                                $album = new Album();
                                $album->key = $child['tag'];
                                $album->title = $child['title'];
                                $album->section = $child['section'];
                                $iconForm = new ImageForm();
                                $iconForm->url = $child['icon'];
                                $icon = $iconForm->save();
                                $album->icon = empty($icon) ? 0 : $icon->id;
                                $album->create_time = time();
                                $album->status = 1;
                                if (!$album->save()) {
                                    $errors = array_merge($errors, $album->getErrors());
                                    var_dump($album->getErrors());
                                    exit;
                                }
                            }
                            $childUrl = "http://bj1.pics.appdao.com/pics?source=wallpaper&tag={$child['tag']}&sort=new&maxid=&page={$page}&limit={$perLimit}&show_type=" .$suffix;
                            $childResponse = $curl->get($childUrl);
                            $childList = Json::decode($childResponse, true);
                            $imgIds = [];
                            foreach($childList['pics'] as $idx => $pic) {
                                echo $pic['stand']['url'] . "\n";
                                $wpImage = WpImage::findOne(['source_url'=>$pic['stand']['url']]);
                                if (!empty($wpImage)) {
                                    continue;
                                }
                                $imgForm = new ImageForm();
                                $imgForm->url = $pic['stand']['url'];
                                $img = $imgForm->save();
                                if ($img) {
                                    $wpImage = WpImage::findOne(['img_id'=>$img->id]);
                                    if (empty($wpImage)) {
                                        $wpImage = new WpImage();
                                        $wpImage->img_id = $img->id;
                                        $wpImage->status = 1;
                                        $wpImage->source_url = $pic['stand']['url'];
                                        if (!$wpImage->save()) {
                                            $errors = array_merge($errors, $wpImage->getErrors());
                                        }
                                        $rel = AlbumImgRel::findOne([
                                            'album_id' => $album->id,
                                            'wp_img_id' => $img->id,
                                        ]);
                                        if (empty($rel)) {
                                            $rel = new AlbumImgRel();
                                            $rel->album_id = $album->id;
                                            $rel->wp_img_id = $wpImage->id;
                                            if (!$rel->save()) {
                                                $errors = array_merge($errors, $rel->getErrors());
                                            }
                                        }
                                        $imgIds[] = $wpImage->id;

                                    } else {
                                        $wpImage->source_url = $pic['stand']['url'];
                                        if (!$wpImage->save()) {
                                            $errors = array_merge($errors, $wpImage->getErrors());
                                        }
                                    }

                                } else {
                                    $errors = array_merge($errors, $imgForm->getErrors());
                                }
                            }
                            $this->finishThread($task->id, 'wallpaper', $childUrl, 'wallpaper/'.$child['tag'] , $imgIds, $errors);
                        }
                    }
                }
            }
            $this->endTask($task->id, json_encode($errors));
        } catch (Exception $e) {
            $errors['Exception'] = [$e->getMessage()];
            $this->endTask($task->id, json_encode($errors));

        }


    }
    public function actionIndex($page = 1)
    {
        $limit = 25;
        $suffix = "&screen_w=750&screen_h=1334&ir=0&app=9P_iPhone5Wallpapers&v=2.8&lang=zh-Hans-CN&"
            . "it=1467781106.738783&ots=5&jb=0&as=0&mobclix=0&deviceid=replaceudid&macaddr=&idv=5492D869-782A-4219-A723-2CB678E66738"
            . "&idvs=&ida=D3DD2A38-5E20-458C-A3DE-AC693C5CFBE6&phonetype=iphone&model=iphone7%2C2&osn=iPhone%20OS&osv=9.3.2&tz=8";
        $curl = new curl\Curl();
        $url = "http://page.appdao.com/page?name=page_new_v1" . $suffix;
        $response = $curl->get($url);
        $wpInfo = Json::decode($response, true);
        $wpCatUrl = "http://page.appdao.com/forward?link=1988107&style={$wpInfo['data'][0]['style']}&item={$wpInfo['data'][0]['fullname']}&page={$page}&limit={$limit}&after=" . $suffix;
        $wpCatResp = $curl->get($wpCatUrl);
        $wpCatInfo = Json::decode($wpCatResp, true);
        foreach ($wpCatInfo['data'] as $oneCat) {
            $album = Album::findOne(['key' => $oneCat['fullname']]);
            if (empty($album)) {
                $album = new Album();
                $album->key = $oneCat['fullname'];
                $album->title = $oneCat['title'];
                $album->create_time = time();
                $album->status = 1;
                if (!$album->save()) {
                    $this->error($album->getErrors());
                    exit;
                }
            }
            if (isset($oneCat['fullname'])) {
                $url = "http://page.appdao.com/redirect?link=3684619"
                    . "&linkurl=" . urlencode("app://forwardtypeinapp=page&name=rw_connect&source=page&fullname=" . $oneCat['fullname'])
                    . "&style=01001&item=45828&userid=0" . $suffix;
                $albumInfoResp = $curl->get($url);
                $albumInfo = Json::decode($albumInfoResp, true);
                $data = $albumInfo["data"][count($albumInfo["data"]) - 1];
                $listUrl = "http://page.appdao.com/forward?link=3684619" . "&linkurl=" . urlencode($data['link']['url'])
                    . "&style=" . $data['style'] . "&item=" . $data['fullname'] . "&page=1&limit=25&after=" . $suffix;;
                echo $listUrl . "\n";
                $listResp = $curl->get($listUrl);
                $list = Json::decode($listResp, true);
                foreach ($list['data'] as $one) {
                    foreach ($one as $oneThird) {
                        if (isset($oneThird["stand"]) && isset($oneThird["stand"]["url"])) {
                            $imgForm = new ImageForm();
                            $imgForm->url = $oneThird["stand"]["url"];
                            $img = $imgForm->save();
                            if ($img) {
                                $wpImage = WpImage::findOne(['img_id'=>$img->id]);
                                if (empty($wpImage)) {
                                    $wpImage = new WpImage();
                                    $wpImage->img_id = $img->id;
                                    $wpImage->status = 1;
                                    if (!$wpImage->save()) {
                                        $this->error($wpImage->getErrors());
                                        exit;
                                    }
                                    $rel = AlbumImgRel::findOne([
                                        'album_id' => $album->id,
                                        'wp_img_id' => $img->id,
                                    ]);
                                    if (empty($rel)) {
                                        $rel = new AlbumImgRel();
                                        $rel->album_id = $album->id;
                                        $rel->wp_img_id = $wpImage->id;
                                        if (!$rel->save()) {
                                            $this->error($rel->getErrors());
                                            exit;
                                        }
                                    }
                                }

                            } else {
                                $this->error($imgForm->getErrors());
                                exit;
                            }

                        }
                    }

                }

            }
        }

    }


    public function actionImportCategory() {
        $albums = Album::find()->all();
        foreach($albums as $album) {
            $cat = Category::findOne(['name'=>$album->section]);
            if (empty($cat)) {
                $cat = new Category();
                $cat->name = $album->section;
                $cat->keyword = '';
                $cat->rank = 0;
                if (!$cat->save()) {
                    var_dump($cat->getErrors());
                    exit;
                }
            }

            $album->category = $cat->id;
            if(!$album->save()) {
                var_dump($album->getErrors());
                exit;
            }
        }
    }
    public function actionInitWpAlbum() {
        $allImages = AlbumImgRel::find()->all();
        foreach($allImages as $one) {
            $wpImg = WpImage::findOne(['img_id'=>$one->wp_img_id]);
            if (!empty($wpImg)) {
                $one->wp_img_id = $wpImg->id;
                $one->save();
            }

        }

    }

    public function actionCheck() {
        $allImages = Image::find()->all();
        foreach($allImages as $one) {
            if (!file_exists(\Yii::$app->params['imgDir'] . $one->file_path)) {
                $one->status = 0;
                $one->save();
                continue;
            }
            if (empty($one->album)) {
                $one->status = 0;
                $one->save();
                continue;
            }
            $one->status = 1;
            $one->save();
        }


    }

}
