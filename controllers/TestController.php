<?php
/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/7/22
 * Time: 14:40
 */

namespace console\controllers;


use linslin\yii2\curl\Curl;
use Yii;
use yii\console\Controller;

class TestController extends Controller
{
    public function actionTest() {
        $curl = new Curl();
        $resp = $curl->get('http://qy1.appcq.cn:8085/comments');


        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(Yii::$app->params['encodeKey']), $resp, MCRYPT_MODE_ECB);
        echo $decrypted;

    }

}