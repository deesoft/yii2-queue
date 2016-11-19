<?php

namespace dee\queue;

use Yii;
use yii\console\Controller;
use Symfony\Component\Process\Process;
use yii\di\Instance;
use yii\helpers\FileHelper;

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
    public $directCall = true;
    private $_day;
    private $_file;

    /**
     *
     * @var string
     */
    private $_scriptFile;

    public function init()
    {
        parent::init();
        $this->queue = Instance::ensure($this->queue, Queue::className());
    }
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
        } else {
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
            $fileCheck = Yii::getAlias("@runtime/queue/listen_check.php");
            $command = PHP_BINARY . " {$this->scriptFile} {$this->uniqueId}/run 2>&1";
            $this->_file = Yii::getAlias('@runtime/queue/' . date('Ym/d') . '.log');
            FileHelper::createDirectory(dirname($this->_file));
            file_put_contents($fileCheck, "<?php\n return true;");
            while (require($fileCheck)) {
                $this->runQueue($command);
                if ($this->sleepTimeout) {
                    sleep($this->sleepTimeout);
                }
                if ($timeout > 0 && time() > $timeout) {
                    break;
                }
            }
            $mutex->release(__METHOD__);
        } else {
            $this->stderr("Already running...\n");
            return self::EXIT_CODE_ERROR;
        }
        $this->stdout("Done..\n");
    }

    public function actionStop()
    {
        $fileCheck = Yii::getAlias("@runtime/queue/listen_check.php");
        FileHelper::createDirectory(dirname($fileCheck));
        file_put_contents($fileCheck, "<?php\n return false;");
    }

    /**
     *
     * @param type $command
     * @param type $cwd
     */
    protected function runQueue($command)
    {
        if ($this->directCall) {
            $this->actionRun();
        } else {
            if ($this->_day != ($d = date('Ym/d'))) {
                $this->_day = $d;
                $this->_file = Yii::getAlias("@runtime/queue/{$d}.log");
                FileHelper::createDirectory(dirname($this->_file));
            }
            $process = new Process("$command >>{$this->_file}");
            $process->run();
        }
    }

    /**
     *
     */
    public function actionRun()
    {
        try {
            return $this->queue->run() === false ? self::EXIT_CODE_ERROR : self::EXIT_CODE_NORMAL;
        } catch (\Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }
}
