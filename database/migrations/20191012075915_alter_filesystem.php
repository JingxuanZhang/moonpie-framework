<?php

/**
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

use think\migration\Migrator;
use think\migration\db\Column;

class AlterFilesystem extends Migrator
{
    protected $uploadGroup = 'system_upload_group';
    protected $uploadFile = 'system_upload_file';
    public function up()
    {
        $this->alterGroup();
        $this->alterFile();
    }
    protected function alterGroup()
    {
        $table = $this->table($this->uploadGroup);
        $newColumn = Column::smallInteger('sort')->setComment('分组排序权重');
        $table->changeColumn($newColumn);
        //新增列用来限制组域
        $scopeColumn = Column::string('scope')->setAfter('sort')->setComment('限制组的作用域')->setDefault('');
        if (!$table->hasColumn($scopeColumn->getName())) {
            $table->addColumn($scopeColumn);
        }
        if (!$table->hasIndex(['group_type', 'scope', 'sort'])) {
            $table->addIndex(['group_type', 'scope', 'sort'], ['name' => 'index_group_by_scope']);
        }
        $table->update();
    }
    protected function alterFile()
    {
        $table = $this->table($this->uploadFile);
        $columnScope = Column::string('scope')->setDefault('')->setComment('文件的作用域，用来确定其对当前用户是否可见')->setAfter('extension');
        if (!$table->hasColumn($columnScope->getName())) {
            $table->addColumn($columnScope)
                ->addColumn(Column::integer('delete_at')->setComment('删除时间')->setAfter('is_delete')->setDefault(0))
                ->addIndex(['scope', 'storage', 'group_id'], ['name' => 'index_storage']);
        }
        //删除不需要的
        if ($table->hasColumn('is_user')) $table->removeColumn('is_user');
        $table->update();
    }
}
