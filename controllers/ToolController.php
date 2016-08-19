<?php
namespace console\controllers;

use yii;
use yii\helpers\Console;
use common\components\Utility;

class ToolController extends \yii\console\Controller
{   
    public function actionSid($sid) { 
        echo Utility::id($sid);
    }
    

}
