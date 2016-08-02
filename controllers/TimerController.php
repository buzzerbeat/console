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
	
}