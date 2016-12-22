<?php

namespace dee\queue;

use Yii;
use yii\console\Controller;
use Symfony\Component\Process\Process;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\caching\Cache;

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
     * @var Cache
     */
    public $cache = 'cache';
    /**
     *
     * @var int
     */
    public $sleep = 0;
    /**
     *
     * @var bool execute job as asynchronous
     */
    public $asynchron = true;
    /**
     *
     * @var string process name
     */
    public $name;
    /**
     *
     * @var string
     */
    public $mutex = 'yii\mutex\FileMutex';
    private $_day;
    private $_file;
    /**
     *
     * @var string
     */
    private $_scriptFile;
    private $_defaultQueue;

    public function init()
    {
        parent::init();
        $this->_defaultQueue = $this->queue;
        if ($this->cache !== null) {
            $this->cache = Instance::ensure($this->cache, Cache::className());
        }
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
     */
    public function actionListen()
    {
        /* @var $mutex \yii\mutex\Mutex */
        $mutex = Yii::createObject($this->mutex);
        $mutexKey = __CLASS__ . $this->name;
        if (empty($this->name) || $mutex->acquire($mutexKey)) {
            $pid = getmypid();
            echo "Run queue listener [$pid] @" . date('Y-m-d H:i:s') . "\n";
            declare(ticks = 1);
            pcntl_signal(SIGTERM, [$this, 'handelSignal']);
            pcntl_signal(SIGINT, [$this, 'handelSignal']);

            // run command
            $options = [];
            if (is_string($this->queue) && $this->queue != $this->_defaultQueue) {
                $options[] = "--queue={$this->queue}";
            }
            if ($this->name) {
                $options[] = "--name={$this->name}";
            }
            $options = implode(' ', $options);
            $command = PHP_BINARY . " {$this->scriptFile} {$this->uniqueId}/run $options 2>&1";

            $start = time() - 60;
            while (true) {
                if ($this->name && time() - $start >= 60 && $this->cache) {
                    $start = time();
                    $this->cache->set([__CLASS__, 'pid', $this->name], $pid, 62 + $this->sleep);
                }
                $this->runQueue($command);
                if ($this->sleep > 0) {
                    sleep($this->sleep);
                }
            }
            if ($this->name) {
                $mutex->release($mutexKey);
            }
        } else {
            $this->stderr("Already running...\n");
            return self::EXIT_CODE_ERROR;
        }
        $this->stdout("Done..\n");
    }

    public function actionStatus()
    {
        if ($this->name && $this->cache) {
            $pid = $this->cache->get([__CLASS__, 'pid', $this->name]);
            if ($pid === false) {
                echo "Not running...\n";
            } else {
                echo "Running...\n";
            }
        }
    }

    public function actionStop()
    {
        if ($this->name && $this->cache) {
            $pid = $this->cache->get([__CLASS__, 'pid', $this->name]);
            posix_kill($pid, SIGKILL);
        }
    }

    public function handelSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
                echo "Caught pcntl signal\n";
                exit(1);
        }
    }

    /**
     *
     * @param type $command
     * @param type $cwd
     */
    protected function runQueue($command)
    {
        if ($this->_day != ($d = date('Ym/d'))) {
            $this->_day = $d;
            $this->_file = Yii::getAlias("@runtime/queue/{$d}_{$this->name}.log");
            FileHelper::createDirectory(dirname($this->_file), 0777);
        }
        $process = new Process("$command >>{$this->_file}");
        if ($this->asynchron) {
            $process->start();
        } else {
            $process->run();
        }
    }

    /**
     * Run queue
     */
    public function actionRun()
    {
        $this->queue = Instance::ensure($this->queue, Queue::className());
        return $this->queue->run() === false ? self::EXIT_CODE_ERROR : self::EXIT_CODE_NORMAL;
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'name', 'asynchron', 'sleep', 'queue'
        ]);
    }
}
