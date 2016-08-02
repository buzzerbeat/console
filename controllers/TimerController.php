<?php
namespace console\controllers;

use yii;
use yii\helpers\Console;
use wallpaper\models\WpImage;

class TimerController extends \yii\console\Controller
{   
    public function actionRandomWpImage(){
        $divisor = rand(0, 9);
        $sql = 'SELECT * FROM  `wp_image` WHERE `id` % 10 = ' . $divisor;
        $rows = WpImage::findBySql($sql)->all();
        $updateNum = 0;
        foreach ($rows as $row) {
            $random = rand(1, 100000);
            $row->setScenario('timer');
            
            $row->setAttributes(['rank'=>$random]);
            if(!$row->save()){
                echo $row->id . " 置为random失败\n";
            }
            $updateNum++;
        }
        
        echo "更新{$updateNum}个壁纸\n";
    }
	
}