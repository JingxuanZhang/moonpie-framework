<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddLogSystem extends Migrator
{
    protected $tableName = 'sys_log';

    public function up()
    {
        if (!$this->hasTable($this->tableName)) {
            $table = $this->table($this->tableName, ['engine' => 'myisam', 'comment' => 'monolog日志系统ThinkPHP数据库版', 'id' => false]);
            $table->addColumn(Column::string('channel', 200)->setComment('日志所属频道'))
                ->addColumn(Column::longText('message')->setComment('日志信息'))
                ->addColumn(Column::smallInteger('level')->setComment('日志等级'))
                ->addColumn(Column::integer('create_at')->setComment('记录添加时间'))
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable($this->tableName)) {
            $this->dropTable($this->tableName);
        }
    }
}
