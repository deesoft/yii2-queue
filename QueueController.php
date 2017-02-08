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
    public $sleep = 1;
    /**
     *
     * @var int
     */
    public $maxProcess = 1;
    /**
     *
     * @var string
     */
    public $outputPath = '@runtime/queue';
    private $_day;
    private $_file;
    /**
     *
     * @var string
     */
    private $_scriptFile;
    private $_defaultQueue;
    private $_outputPath;
    private $_processes;

    public function init()
    {
        parent::init();
        $this->_defaultQueue = $this->queue;
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
        $pid = getmypid();

        echo "Run queue listener [$pid] @" . date('Y-m-d H:i:s') . "\n";
        declare(ticks = 1);
        pcntl_signal(SIGTERM, [$this, 'handelSignal']);
        pcntl_signal(SIGINT, [$this, 'handelSignal']);

        // run command
        $commands = [
            PHP_BINARY,
            $this->scriptFile,
            $this->uniqueId.'/run',
        ];
        if (is_string($this->queue) && $this->queue != $this->_defaultQueue) {
            $commands[] = "--queue={$this->queue}";
        }
        $commands[] = '2>&1';
        $command = implode(' ', $commands);
        if ($this->outputPath) {
            $this->_outputPath = Yii::getAlias($this->outputPath);
        }
        if ($this->maxProcess > 1) {
            $this->_processes = array_fill(0, $this->maxProcess - 1, false);
        }
        while (true) {
            $this->runQueue($command);
        }
        $this->stdout("Done..\n");
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
     * @param string $command
     */
    protected function runQueue($command)
    {
        if ($this->maxProcess > 1) {
            $wait = true;
            $sleep = round(1000000 * ($this->sleep > 1 ? $this->sleep : 1) / $this->maxProcess);
            while ($wait) {
                foreach ($this->_processes as $i => $process) {
                    usleep($sleep);
                    if ($process === false || !$process->isRunning()) {
                        if ($process) {
                            $this->_processes[$i] = false;
                            unset($process);
                        }
                        $index = $i;
                        $wait = false;
                        break;
                    }
                }
            }
        } else {
            sleep($this->sleep > 1 ? $this->sleep : 1);
        }
        if ($this->_outputPath) {
            if ($this->_day != ($d = date('Ym/d'))) {
                $this->_day = $d;
                $this->_file = "{$this->_outputPath}/{$d}.log";
                FileHelper::createDirectory(dirname($this->_file), 0777);
            }
            $command .= " >>{$this->_file}";
        }

        $process = new Process($command);
        if ($this->maxProcess > 1) {
            $process->start();
            $this->_processes[$index] = $process;
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
            'maxProcess', 'sleep', 'queue'
        ]);
    }
}
