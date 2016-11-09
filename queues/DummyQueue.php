<?php

namespace dee\queue\queues;

/**
 * Description of DummyQueue
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DummyQueue extends \dee\queue\Queue
{
    protected function popJob()
    {
        return false;
    }

    protected function pushJob($message, $delay = 0)
    {

    }

    public function push($route, $payload = [], $delay = 0)
    {
        return $this->runJob($route, $payload);
    }

}
