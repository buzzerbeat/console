<?php
namespace console\controllers;

use yii;
use yii\helpers\Console;
use wallpaper\models\WpImage;
use microvideo\models\MvTag;
use microvideo\models\MvVideoTagRel;
use microvideo\models\MvVideo;

class TimerController extends \yii\console\Controller
{   
    public function actionRandomMvVideo(){
        $sTime = microtime(true);
        $divisor = rand(0,9);
        $sql = 'SELECT * FROM  `mv_video` WHERE `id` % 10 = ' . $divisor;
        $rows = MvVideo::findBySql($sql)->all();
        $num = 0;
        foreach($rows as $row){
            $random = rand(1, 100000);
            $row->setAttributes(['rank'=>$random]);
            if(!$row->save()){
                echo $row->id . " 置为random失败\n";
                continue;
            }
            $num++;
        }
        
        echo "更新视频{$num}个，耗时" . round((microtime(true)-$sTime)*10)/10 . "秒\n";
    }
    
    public function actionRandomWpImage(){
        $sTime = microtime(true);
        $divisor = rand(0, 9);
        $sql = 'SELECT * FROM  `wp_image` WHERE `id` % 10 = ' . $divisor;
        $rows = WpImage::findBySql($sql)->all();
        $num = 0;
        foreach ($rows as $row) {
            $random = rand(1, 100000);
            $row->setAttributes(['rank'=>$random]);
            if(!$row->save()){
                echo $row->id . " 置为random失败\n";
                continue;
            }
            $num++;
        }
        
        echo "更新壁纸{$num}个，耗时" . round((microtime(true)-$sTime)*10)/10 . "秒\n";
    }
	    
    /*
     * 定时更新tag包含资源数
     * */
    public function actionUpdateVideoTagCount(){
        $sTime = microtime(true);
        $tags = MvTag::find()->all();
        $num = 0;
        foreach($tags as $tag){
            $count = MvVideoTagRel::find()->where(['mv_tag_id'=>$tag->id])->count();
            $tag->setAttributes(['count'=>$count]);
            if(!$tag->save()){
                print_r($tag->getFirstErrors());
                continue;
            }
            $num++;
        }
         
        echo "更新标签{$num}个，耗时" . round((microtime(true)-$sTime)*10)/10 . "秒\n";
    }
    

}
