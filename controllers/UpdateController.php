<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

use backend\models\microvideo\MvTag;
use backend\models\microvideo\MvVideoTagRel;

class UpdateController extends controller{
    /*
     * 定时更新tag包含资源数
    * */
    public function actionUpdateMvVideoTagCount(){
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