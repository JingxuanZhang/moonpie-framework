<?php

use think\migration\Migrator;
use think\migration\db\Column;

class AlterAclSystem extends Migrator
{
    protected $tableAlter = 'acl_resource';
    protected $tableRule = 'acl_user_acl_grant';

    public function up()
    {
        if ($this->hasTable($this->tableAlter)) {
            $table = $this->table($this->tableAlter);
            //将resource_type字段替换成assertion字段
            /*if(!$table->hasColumn('assertion')) {
                $newColumn = Column::string('assertion')->setComment('使用的断言类标识')->setDefault('');
                $needSave = true;
                if ($table->hasColumn('resource_type')) {
                    $table->changeColumn('resource_type', $newColumn);
                }else {
                    $table->addColumn($newColumn->setAfter('id'));
                }
            }*/
            if ($table->hasColumn('resource_type')) {
                $table->removeColumn('resource_type');
            }
            if ($table->hasIndex('resource_id', ['name' => 'unique_resource'])) {
                $table->removeIndexByName('unique_resource');
            }
            if (!$table->hasIndex('resource_id', ['name' => 'idx_resource_id'])) {
                $table->addIndex('resource_id', ['name' => 'idx_resource_id']);
            }

            //新增code字段，用来直接存储资源的标识
            if (!$table->hasColumn('code')) {
                $table->addColumn(Column::string('code', 40)->setNullable()->setComment('资源唯一标识')->setUnique()->setAfter('resource_id'));
                $needSave = true;
            }
            if (isset($needSave) && $needSave) {
                $table->update();
            }
        }
        if ($this->hasTable($this->tableRule)) {
            $needSave = false;
            $table = $this->table($this->tableRule);
            if (!$table->hasColumn('assertion')) {
                $newColumn = Column::string('assertion')->setComment('使用的断言类标识')->setDefault('')
                    ->setAfter('resource_id');
                $table->addColumn($newColumn);
                $needSave = true;
            }
            if (!$table->hasColumn('title')) {
                $newColumn = Column::string('title', 40)->setComment('简单描述规则信息')->setAfter('id');
                $table->addColumn($newColumn);
                $needSave = true;
            }
            if (!$table->hasColumn('use_code')) {
                $newColumn = Column::boolean('use_code')->setComment('是否使用code而非实例')->setAfter('resource_id');
                $table->addColumn($newColumn);
                $needSave = true;
            }
            if ($needSave) {
                $table->update();
            }
        }
    }

    public function down()
    {
        $table = $this->table($this->tableAlter);
        if ($table->hasColumn('assertion')) {
            $table->removeColumn('assertion');
            $needSave = true;
        }
        if ($table->hasColumn('code')) {
            $table->removeColumn('code');
            $needSave = true;
        }
        if (isset($needSave) && $needSave) {
            $table->update();
        }
        if ($table->hasIndex('resource_id', ['name' => 'idx_resource_id'])) {
            $table->removeIndexByName('idx_resource_id');
        }
        if ($this->hasTable($this->tableRule)) {
            $table = $this->table($this->tableRule);
            if ($table->hasColumn('assertion')) {
                $table->removeColumn('assertion');
            }
            if ($table->hasColumn('title')) {
                $table->removeColumn('title');
            }
            if ($table->hasColumn('use_code')) {
                $table->removeColumn('use_code');
            }
        }
    }
}
