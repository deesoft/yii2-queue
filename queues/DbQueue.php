<?php

namespace dee\queue\queues;

use Yii;
use yii\di\Instance;
use yii\db\Connection;
use yii\db\Query;

/**
 * Description of DbQueue
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DbQueue extends \dee\queue\Queue
{
    /**
     *
     * @var Connection 
     */
    public $db = 'db';
    public $tableName = '{{%queue}}';

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    protected function createTable()
    {
        if ($this->db->getTableSchema($this->tableName) === null) {
            $this->db->createCommand()
                ->createTable($this->tableName, [
                    'id' => 'bigpk',
                    'time' => 'integer',
                    'data' => 'binary',
                ])->execute();
            $this->db->getTableSchema($this->tableName, true);
        }
    }

    protected function popJob()
    {
        $query = new Query;
        $query->select(['id', 'data'])
            ->from($this->tableName)
            ->where(['<', 'time', time()])
            ->orderBy(['id' => SORT_ASC])
            ->limit(1);

        if ($this->db->enableQueryCache) {
            // temporarily disable and re-enable query caching
            $this->db->enableQueryCache = false;
            $row = $query->createCommand($this->db)->queryOne();
            $this->db->enableQueryCache = true;
        } else {
            $row = $query->createCommand($this->db)->queryOne();
        }
        if ($row !== false) {
            $this->db->createCommand()->delete($this->tableName, ['id' => $row['id']])->execute();
            if (is_resource($row['data']) && get_resource_type($row['data']) === 'stream') {
                return stream_get_contents($row['data']);
            }
            return $row['data'];
        }
        return false;
    }

    protected function pushJob($message, $delay = 0)
    {
        return $this->db->createCommand()->insert($this->tableName, [
                'data' => [$message, \PDO::PARAM_LOB],
                'time' => $delay > 0 ? time() + $delay : 0,
            ])->execute();
    }
}
