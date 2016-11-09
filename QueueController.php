<?php

namespace dee\queue;

use Yii;
use yii\console\Controller;
use Symfony\Component\Process\Process;
use yii\di\Instance;
/**
 * Description of QueueController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class QueueController extends Controller
{
    public $scriptFile = '@app/yii';
    public $queue = 'queue';
    public $sleepTime;

    public function actionListen()
    {
        $command = PHP_BINARY . ' ' . Yii::getAlias($this->scriptFile) . " {$this->uniqueId}/run";
        $cwd = getcwd();
        while (true) {
            $this->runQueue($command, $cwd);
            if($this->sleepTime){
                sleep($this->sleepTime);
            }
        }
    }

    protected function runQueue($command, $cwd)
    {
        $process = new Process($command, $cwd);
        $process->run();
        if ($process->isSuccessful()) {
            $this->stdout($process->getOutput() . PHP_EOL);
        } else {
            $this->stdout($process->getErrorOutput() . PHP_EOL);
        }
    }

    public function actionRun()
    {
        $this->queue = Instance::ensure($this->queue, Queue::class);
        
    }
}
