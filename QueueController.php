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
            if (strncmp($cmd, '/', 1) === 0) {
                $this->_scriptFile = $cmd;
            } else {
                $this->_scriptFile = getcwd() . '/' . $cmd;
            }
        }
        return $this->_scriptFile;
    }

    public function setScriptFile($value)
    {
        if ($value) {
            $this->_scriptFile = realpath(Yii::getAlias($value));
        }  else {
            $this->_scriptFile = null;
        }
        
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
        if ($mutex->acquire(__METHOD__)) {
            echo $command = PHP_BINARY . " {$this->scriptFile} {$this->uniqueId}/run 2>&1 >>";
            $d = false;
            while (true) {
                if ($d != date('Ym/d')) {
                    $d = date('Ym/d');
                    $file = Yii::getAlias("@runtime/queue/{$d}.log");
                    \yii\helpers\FileHelper::createDirectory(dirname($file));
                    $cmd = $command . $file;
                }
                $this->runQueue($cmd);
                if ($this->sleepTimeout) {
                    sleep($this->sleepTimeout);
                }
                if ($timeout > 0 && time() > $timeout) {
                    break;
                }
            }
        } else {
            $this->stderr("Already running...\n");
            return self::EXIT_CODE_ERROR;
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
    }

    /**
     *
     */
    public function actionRun()
    {
        $this->queue = Instance::ensure($this->queue, Queue::className());
        $result = $this->queue->run();
        if ($result !== null) {
            return $result === false ? self::EXIT_CODE_ERROR : self::EXIT_CODE_NORMAL;
        }
    }
}
