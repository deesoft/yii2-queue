<?php

namespace dee\queue;

use Yii;
use yii\base\Module;

/**
 * Description of Queue
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
abstract class Queue extends \yii\base\Object
{
    /**
     *
     * @var Module
     */
    public $module;
    /**
     *
     */
    abstract protected function pushJob($message, $delay = 0);
    /**
     *
     */
    abstract protected function popJob();

    /**
     *
     * @param string $route
     * @param mixed $payload
     * @param int $delay
     * @return bool
     */
    public function push($route, $payload = [], $delay = 0)
    {
        $message = serialize([$route, $payload]);
        return $this->pushJob($message, $delay);
    }

    /**
     *
     * @return type
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
     * 
     */
    public function run()
    {
        list($route, $payload) = $this->pop();
        $result = $this->runJob($route, $payload);
        if ($result === false) {
            $this->push($route, $payload);
        }
        return $result;
    }

    /**
     *
     * @param string $route
     * @param mixed $payload
     * @return bool
     */
    protected function runJob($route, $payload = [])
    {
        return $this->getModule()->runAction($route, $payload);
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
