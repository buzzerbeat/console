<?php
namespace console\controllers;
use common\models\CrawlTask;
use common\models\CrawlThread;
use Yii;
use yii\helpers\Console;

/**
 * Created by PhpStorm.
 * User: cx
 * Date: 2016/7/5
 * Time: 15:08
 */
class BaseController extends \yii\console\Controller
{
    public $elapsedTime = 0;
    const CONSOLE_TIMEOUT = 1800;
    protected function createTask() {
        $command = Yii::$app->controller->id . '/' . Yii::$app->controller->action->id;
        $crawlTask = CrawlTask::findOne([
            'command'=>$command,
            'status'=>CrawlTask::STATUS_RUNNING,
        ]);
        if (!empty($crawlTask)) {
            if (time() - $crawlTask->start_time > self::CONSOLE_TIMEOUT) {
                $this->endTask($crawlTask->id);
            }
            return false;
        }

        $crawlTask = new CrawlTask();
        $crawlTask->command = $command;
        $crawlTask->start_time = time();
        $crawlTask->status = CrawlTask::STATUS_RUNNING;
        if (!$crawlTask->save()) {
            print_r($crawlTask->getErrors());
            return false;
        }
        return $crawlTask;
    }

    protected function finishThread($taskId, $site, $url, $key, $entityIds, $errorJson = "", $total = 0, $success = 0, $fail = 0, $duplicate = 0, $filter = 0) {
        $crawlThread = new CrawlThread();
        $crawlThread->status = empty($errorJson) ? CrawlThread::STATUS_SUCCESS : CrawlThread::STATUS_FAIL;
        $crawlThread->error_json = json_encode($errorJson);
        $crawlThread->url = $url;
        $crawlThread->site = $site;
        $crawlThread->task_id = $taskId;
        $crawlThread->key = $key;
        $crawlThread->total_num = $total;
        $crawlThread->success_num = $success;
        $crawlThread->fail_num = $fail;
        $crawlThread->duplicate_num = $duplicate;
        $crawlThread->filter_num = $filter;
        $crawlThread->entity_id = json_encode($entityIds);
        if ($this->elapsedTime > 0) {
            $crawlThread->duration = intval(Yii::getLogger()->elapsedTime) - $this->elapsedTime;
        } else {
            $crawlThread->duration = intval(Yii::getLogger()->elapsedTime);
            $this->elapsedTime = intval(Yii::getLogger()->elapsedTime);
        }
        $crawlThread->time = time() - $crawlThread->duration;
        if (!$crawlThread->save()) {
            print_r($crawlThread->getErrors());
            return false;
        }
        return true;

    }
    protected function endTask($taskId, $errorJson = "") {
        $successNum = CrawlThread::find()
            ->where([
                'task_id'=>$taskId,
                'status'=>CrawlThread::STATUS_SUCCESS,
            ])
            ->count();
        $failNum = CrawlThread::find()
            ->where([
                'task_id'=>$taskId,
                'status'=>CrawlThread::STATUS_FAIL,
            ])
            ->count();
        $crawlTask = CrawlTask::findOne($taskId);
        if (!empty($crawlTask)) {
            $crawlTask->success_num = $successNum;
            $crawlTask->fail_num = $failNum;
            $crawlTask->end_time = time();
            $crawlTask->status = CrawlTask::STATUS_FINISH;
            $crawlTask->error_json = $errorJson;
            if (!$crawlTask->save()) {
                print_r($crawlTask->getErrors());
                return false;
            }
            return true;
        } else {
            echo "任务{$taskId}不存在\n";
            return false;
        }
    }


    protected function error($errors) {
//        debug_print_backtrace();
        var_dump($errors);
//        exit;
//        var_dump($errors);
//        Console::error('Errors' . $errors);
    }

}