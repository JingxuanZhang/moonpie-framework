<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

use think\migration\db\Column;
use think\migration\Migrator;

class BindAdminUserRoleSystem extends Migrator
{
    protected $tableName = 'admin_user';

    public function up()
    {
        $table = $this->table($this->tableName);
        $columnNew = Column::integer('role_id')->setAfter('username')->setComment('关联的角色ID')->setNullable();
        if (!$table->hasColumn($columnNew->getName())) {
            $table->addColumn($columnNew);
            $table->update();
        }
    }

    public function down()
    {
        $table = $this->table($this->tableName);
        $columnNew = Column::integer('role_id')->setAfter('username')->setComment('关联的角色ID')->setNullable();
        if ($table->hasColumn($columnNew->getName())) {
            $table->removeColumn($columnNew->getName())->update();
        }
    }
}
