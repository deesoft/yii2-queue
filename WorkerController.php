<?php

namespace dee\queue;

/**
 * Description of WorkerController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class WorkerController extends \yii\console\Controller
{
    private $_actionParams;

    /**
     * @inheritdoc
     */
    public function bindActionParams($action, $params)
    {
        $this->_actionParams = $params;
        return parent::bindActionParams($action, $params);
    }

    /**
     * @return array
     */
    public function getActionParams()
    {
        return $this->_actionParams;
    }
}
