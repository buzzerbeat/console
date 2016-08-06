<?php
namespace console\controllers;

use yii;
use yii\helpers\Console;
use wallpaper\models\WpImage;

class TimerController extends \yii\console\Controller
{   
    public function actionRandomWpImage(){
        $divisor = rand(1, 10);
        $sql = 'SELECT * FROM  `album` WHERE `id` % 10 = ' . $divisor;
        $rows = WpImage::findBySql($sql)->all();
        foreach ($rows as $row) {
            $random = rand(1, 100000);
            $row->setScenario('timer');
            
            $row->setAttributes(['rank'=>$random]);
            if(!$row->save()){
                //print_r($row->getFirstErrors());
                echo $row->id . " 置为random失败\n";
            }
        }
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
