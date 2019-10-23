<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AddAdminTable extends Migrator
{
    protected $tableAdmin = 'admin_user';
    protected $tableSetting = 'site_setting';


    public function up()
    {
        if (!$this->hasTable($this->tableAdmin)) {
            $table = $this->table($this->tableAdmin, ['engine' => 'InnoDB', 'comment' => '管理员表']);
            $table
                ->addColumn(Column::string('username', 100)->setComment('登录账户')->setUnique())
                ->addColumn(Column::char('password', 32)->setComment('登录密码'))
                ->addColumn(Column::integer('create_at')->setComment('创建时间'))
                ->addColumn(Column::integer('update_at')->setComment('更新时间'))
                ->create();

        }
        if(!$this->hasTable($this->tableSetting)){
            $table = $this->table($this->tableSetting, ['engine' => 'myisam', 'id' => false, 'comment' => '站点配置表']);
            $table->addColumn(Column::string('key', 100)->setUnique()->setComment('配置索引'))
                ->addColumn(Column::string('describe', 200)->setComment('配置描述'))
                ->addColumn(Column::longText('values')->setComment('配置内容'))
                ->addColumn(Column::integer('update_at')->setUnsigned()->setComment('修改时间'))
                ->create();
        }

    }


    public function down()
    {
        if ($this->hasTable($this->tableAdmin)) {
            $this->dropTable($this->tableAdmin);
        }
        if($this->hasTable($this->tableSetting)){
            $this->dropTable($this->tableSetting);
        }
    }
}

