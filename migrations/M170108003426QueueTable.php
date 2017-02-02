<?php

namespace dee\queue\migrations;

use yii\db\Migration;

class M170108003426QueueTable extends Migration
{

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        if ($this->db->getTableSchema('{{%dee_queue}}', true) === null) {
            $this->createTable('{{%dee_queue}}', [
                'id' => 'bigpk',
                'time' => 'integer',
                'data' => 'text',
                ], $tableOptions);
        }
    }

    public function down()
    {
        if ($this->db->getTableSchema('{{%dee_queue}}', true) !== null) {
            $this->dropTable('{{%dee_queue}}');
        }
    }
}
