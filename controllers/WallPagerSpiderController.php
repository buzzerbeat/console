<?php

namespace console\controllers;
use common\models\Image;
use common\models\ImageForm;
use console\models\DaoappDeskApp;
use wallpaper\models\AlbumImgRel;
use wallpaper\models\Album;
use wallpaper\models\Category;
use wallpaper\models\WpImage;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use linslin\yii2\curl;

class WallPagerSpiderController extends BaseController
{
    public function actionTest()
    {
        $collect = new DaoappDeskApp();
        $collect->listIndex();
    }
}