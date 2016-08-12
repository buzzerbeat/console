<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

use backend\models\microvideo\MvTag;
use backend\models\microvideo\MvVideoTagRel;
use common\models\Image;

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

    public function actionWallpaperCover(){
    	$albums = Album::find()->where(['icon'=>['', 0]])->all();
    	foreach($albums as $album){
    		$rel = AlbumImgRel::find()->where(['album_id'=>$album->id])->orderBy('id asc')->one();
    		if(!empty($rel)){
    		    $img = WpImage::findOne($rel->wp_img_id);
    			$album->setAttributes(['icon'=>$img->img_id]);
    			if(!$album->save()){
    				echo "图集" . $album->id . "保存封面图失败\n";
    			}
    		}
    		else{
    			echo "图集" . $album->id . "获取封面图失败\n";
    		}
    	}
    }
    

	public function actionDeleteNoFileImg() {
		$imgs = Image::find()->where('id%10 = 0')->all();
		foreach ($imgs as $img) {
			$file = yii::$app->params['imgDir'] . $img->file_path;
			if (!file_exists($file)) {
				echo "$file\n";
				$img->delete();
			}
		}
	}
}
