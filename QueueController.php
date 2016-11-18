<?php

namespace dee\queue;

use Yii;
use yii\console\Controller;
use Symfony\Component\Process\Process;
use yii\di\Instance;

/**
 * Description of QueueController
 *
 * @property string $scriptFile
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class QueueController extends Controller
{
    /**
     *
     * @var Queue
     */
    public $queue = 'queue';

    /**
     *
     * @var int
     */
    public $sleepTimeout;

    /**
     *
     * @var string
     */
    public $mutex = 'yii\mutex\FileMutex';

    /**
     *
     * @var string
     */
    private $_scriptFile;

    public function getScriptFile()
    {
        if ($this->_scriptFile === null) {
            $cmd = $_SERVER['argv'][0];
            if (strncmp($cmd, './', 2) === 0) {
                $cmd = substr($cmd, 2);
            }
            $this->_scriptFile = getcwd() . '/' . $cmd;
        }
        return $this->_scriptFile;
    }

    public function setScriptFile($value)
    {
        $this->_scriptFile = realpath(Yii::getAlias($value));
    }

    /**
     *
     * @param int $timeout
     */
    public function actionListen($timeout = 0)
    {
        /* @var $mutex \yii\mutex\Mutex */
        $mutex = Yii::createObject($this->mutex);
        if ($timeout > 0) {
            $timeout = time() + $timeout;
        }
        $command = PHP_BINARY . " {$this->scriptFile} {$this->uniqueId}/run";
        if ($mutex->acquire(__METHOD__)) {
            while (true) {
                $this->runQueue($command);
                if ($this->sleepTimeout) {
                    sleep($this->sleepTimeout);
                }
                if ($timeout > 0 && time() > $timeout) {
                    break;
                }
            }
        } else {
            $this->stdout("Already running...\n");
        }
    }

    /**
     *
     * @param type $command
     * @param type $cwd
     */
    protected function runQueue($command)
    {
        $process = new Process($command);
        $process->run();
        if ($process->isSuccessful()) {
            $this->stdout($process->getOutput());
        } else {
            $this->stdout($process->getErrorOutput());
        }
    }

    /**
     *
     */
    public function actionRun()
    {
        $this->queue = Instance::ensure($this->queue, Queue::className());
        return $this->queue->run() !== false ? self::EXIT_CODE_NORMAL : self::EXIT_CODE_ERROR;
    }
}
