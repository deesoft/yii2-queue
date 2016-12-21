<?php

namespace dee\queue;

use Yii;
use yii\base\Module;
use yii\base\Component;

/**
 * Description of Queue
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
abstract class Queue extends Component
{
    /**
     * @event Event raised when job is fail.
     */
    const EVENT_FAIL = 'fail';

    /**
     * @var int try to execute before fail. 
     */
    public $defaultExecutionTime = 3;
    /**
     * @var Module
     */
    public $module;

    /**
     * Push job to queue
     * @param string $message
     * @param int $delay delay before task executed
     */
    abstract protected function pushJob($message, $delay = 0);

    /**
     * Pop job from queue
     * @return string.
     */
    abstract protected function popJob();

    /**
     *
     * @param string $route
     * @param mixed $payload
     * @param int $delay
     * @return bool
     */
    public function push($route, $payload = [], $delay = 0, $execution = null)
    {
        if ($execution === null) {
            $execution = $this->defaultExecutionTime;
        }
        $message = serialize([$route, $payload, $execution]);
        return $this->pushJob($message, $delay);
    }

    /**
     *
     * @return array|bool 
     */
    public function pop()
    {
        $message = $this->popJob();
        if ($message) {
            return unserialize($message);
        }
        return false;
    }

    /**
     * Run top of job in queue.
     */
    public function run()
    {
        if ($job = $this->pop()) {
            list($route, $payload, $execution) = $job;
            $result = $this->runJob($route, $payload);
            if ($result === false) {
                if ($execution > 1) {
                    $this->push($route, $payload, 0, $execution - 1);
                } else {
                    $this->trigger(self::EVENT_FAIL, new Event(['route' => $route, 'payload' => $payload]));
                }
            }
            return $result !== false;
        }
    }

    /**
     *
     * @param string $route
     * @param mixed $payload
     * @return bool
     */
    protected function runJob($route, $payload = [])
    {
        try {
            return $this->getModule()->runAction($route, $payload);
        } catch (\Exception $exc) {
            Yii::error(YII_DEBUG ? $exc->getTraceAsString() : $exc->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     *
     * @return Module
     */
    protected function getModule()
    {
        if (!($this->module instanceof Module)) {
            $this->module = empty($this->module) ? Yii::$app : Yii::$app->getModule($this->module);
        }
        return $this->module;
    }
}
