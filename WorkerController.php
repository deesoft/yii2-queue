<?php

namespace dee\queue;

use Yii;
use yii\base\InlineAction;
use yii\console\Exception;
use yii\base\Controller;

/**
 * Description of WorkerController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class WorkerController extends Controller
{
    /**
     * @var array
     */
    private $_actionParams;

    /**
     * @inheritdoc
     */
    public function bindActionParams($action, $params)
    {
        $this->_actionParams = $params;
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = array_values($params);

        $missing = [];
        foreach ($method->getParameters() as $i => $param) {
            if ($param->isArray() && isset($args[$i])) {
                $args[$i] = preg_split('/\s*,\s*/', $args[$i]);
            }
            if (!isset($args[$i])) {
                if ($param->isDefaultValueAvailable()) {
                    $args[$i] = $param->getDefaultValue();
                } else {
                    $missing[] = $param->getName();
                }
            }
        }

        if (!empty($missing)) {
            throw new Exception(Yii::t('yii', 'Missing required arguments: {params}', ['params' => implode(', ', $missing)]));
        }

        return $args;
    }
    
    /**
     * @return array
     */
    public function getActionParams()
    {
        return $this->_actionParams;
    }
}
