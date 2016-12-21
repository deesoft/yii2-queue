<?php

namespace dee\queue;

/**
 * Description of Event
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Event extends \yii\base\Event
{
    /**
     * @var string
     */
    public $route;
    /**
     * @var mixed
     */
    public $payload;
    /**
     * @var bool
     */
    public $isValid = true;
}
