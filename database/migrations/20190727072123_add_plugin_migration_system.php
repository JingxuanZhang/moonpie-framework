<?php

use think\migration\db\Column;
use think\migration\Migrator;

class AddPluginMigrationSystem extends Migrator
{
    protected $tableMigration = 'plugin_migrations';

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        if (!$this->hasTable($this->tableMigration)) {
            $table = $this->table($this->tableMigration, ['engine' => 'InnoDB', 'comment' => '插件数据表迁移信息']);
            $table->addColumn(Column::string('plugin_code', 20)->setComment('插件机读码'))
                ->addColumn(Column::string('plugin_version', 20)->setComment('所属插件版本'))
                ->addColumn('migration_version', 'biginteger', array('limit' => 14, 'comment' => '迁移版本号'))
                ->addColumn('migration_name', 'string', array('limit' => 100, 'default' => null, 'null' => true, 'comment' => '迁移文件名'))
                ->addColumn('start_time', 'datetime', array('default' => 'CURRENT_TIMESTAMP', 'comment' => '迁移执行开始时间'))
                ->addColumn('end_time', 'datetime', array('default' => 'CURRENT_TIMESTAMP', 'comment' => '迁移执行结束时间'))
                ->addColumn('breakpoint', 'boolean', array('default' => false, 'comment' => '断点'))
                ->addColumn(Column::integer('create_at')->setComment('记录添加时间'))
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间'))
                ->addIndex(['plugin_code', 'plugin_version'], ['name' => 'index_plugin_version'])
                ->addIndex(['plugin_code', 'migration_version'], ['name' => 'unique_plugin_migration_version', 'unique' => true])
                ->create();
        }
    }

    public function down()
    {
        if($this->hasTable($this->tableMigration)){
            $this->dropTable($this->tableMigration);
        }
    }
}
