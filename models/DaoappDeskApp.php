<?php
namespace console\models;
use common\models\Image;
use common\models\ImageForm;
use wallpaper\models\WpImageForm;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use linslin\yii2\curl;

class DaoappDeskApp {

    public function listIndex(){
        $maxpage = ceil((time() - 1469526766)/3600) + 10;
        $suffix = "it"
            ."em=45828&page=1&limit=25&after=&screen_w=1242&screen_h=2208&ir=0&app=9P_iPhone5Wallpapers&v=2.8"
            . "&lang=zh-Hans-CN&it=1466406104.025594&ots=34&jb=0&as=0&mobclix=0&deviceid=replaceudid&macaddr=&"
            . "idv=E7D7846B-8430-470D-9177-9FC371752EE6&idvs=&ida=F11EC9F2-A9FC-4E35-B243-B09E93754CA3&phonetype=iphone&model=iphone7%2C1&osn=iPhone%20OS&osv=9.3.2&tz=8";
        $curl = new curl\Curl();
        $url = "http://page.appdao.com/forward?link=1988107&style=051071101201&it"
            ."em=45828&page=1&limit=25&after=&screen_w=1242&screen_h=2208&ir=0&app=9P_iPhone5Wallpapers&v=2.8"
            . "&lang=zh-Hans-CN&it=1466406104.025594&ots=34&jb=0&as=0&mobclix=0&deviceid=replaceudid&macaddr=&"
            . "idv=E7D7846B-8430-470D-9177-9FC371752EE6&idvs=&ida=F11EC9F2-A9FC-4E35-B243-B09E93754CA3&phonetype=iphone&model=iphone7%2C1&osn=iPhone%20OS&osv=9.3.2&tz=8";
        echo $url . "\n";
        $response = $curl->get($url);
        $catList = Json::decode($response, true);
        $usefuleData = $catList['data'];
        foreach($usefuleData as $d) {
            if (!empty($d['pictures'])) {
                echo $d['title'] . " " . $d['fullname'] . "\n" ;
                foreach ($d['pictures'] as $p){
                    echo "\t" . $p['stand']['url'] . "\n";
                    echo "\t" . $p['tags'] . "\n";
                    $imageform = new WpImageForm();
                    $imageform->url = $p['stand']['url'];
                    $imageform->albumname = $d['title'];
                    $imageform->save();
                }
                $after = 0;
                $page = 1;
                while ($after != -1) {
                    $urlKey = 'app://forwardtypeinapp=connect&name=PictureProxy&args=' . $d['fullname'] . '&source=connect';
                    $url = 'http://page.appdao.com/forward?link=' . (1988107+rand(1, 1000000)) . '&linkurl='
                        . urlencode($urlKey)
                        . '&style=051071101201&page=' . $page . "&after=" . $after . "&" . $suffix;
                    echo $url . "\n";
                    echo $urlKey . "\n";
                    $response = $curl->get($url);
                    //echo $response;
                    $pagejson = Json::decode($response, true);
                    $after = $pagejson['after'];
                    foreach ($pagejson['pics'] as $p) {

                            if (!empty($p['stand']['url'])) {
                                if (preg_match('/(;[\d\/]+\..*)$/si', $p['stand']['url'], $match)) {
                                    echo "\t" . 'http://wps.appdao.com/' . substr($match[1], 1) . "\n";
                                    ;
                                    $imageform = new WpImageForm();
                                    $imageform->url = 'http://wps.appdao.com/' . substr($match[1], 1);
                                    $imageform->albumname = $d['title'];
                                    $imageform->save();
                                }
                            }
                            else {
                                print_r($p);
                            }

                    }
                    $page++;
                    if ($page > $maxpage) {
                        break;
                    }
                    sleep(5);
                }

            }
            elseif (!empty($d['data']['link']['url'])) {
                if (strstr($d['redirectlink']['url'], 'source=wp')) {
                    $uuurl = $d['redirectlink']['url'];
                }
                else {
                    $uuurl = $d['data']['link']['url'];
                }
                if (!strstr($uuurl, 'source=wp')) {
                    $uuurl += '&source=wp';
                }
                parse_str($uuurl, $pstr);
                if (empty($pstr['title'])) {
                    continue;
                }
                echo $pstr['title'] . "\n";
                echo "\t" . $uuurl . "\n";
                //print_r($d);
                $after = 0;
                $page = 1;
                while ($after != -1) {
                    $url = 'http://page.appdao.com/forward?link=' . (1988107+rand(1, 1000000)) . '&linkurl='
                        . urlencode($uuurl)
                        . '&page=' . $page . "&after=" . $after . "&style=051108&" . $suffix;
                    echo $url . "\n";
                    $response = $curl->get($url);
                    //echo $response;
                    $pagejson = Json::decode($response, true);
                    if (empty($pagejson['after'])) {
                        break;
                    }
                    $after = $pagejson['after'];
                    foreach ($pagejson['data'] as $ps) {
                        foreach ($ps as $p) {
                            if (!empty($p['stand']['url'])) {
                                if (preg_match('/(;[\d\/]+\..*)$/si', $p['stand']['url'], $match)) {
                                    echo "\t" . 'http://wps.appdao.com/' . substr($match[1], 1) . "\n";
                                    ;
                                    $imageform = new WpImageForm();
                                    $imageform->url = 'http://wps.appdao.com/' . substr($match[1], 1);
                                    $imageform->albumname = $pstr['title'];
                                    $imageform->save();
                                }
                            }
                            else {
                                //print_r($p);
                            }
                        }

                    }
                    $page++;
                    if ($page > $maxpage) {
                        break;
                    }
                    sleep(5);
                }
            }
            else {
                print_r($d);
            }
            ;
        }
    }
}