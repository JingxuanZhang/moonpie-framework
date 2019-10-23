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

/**
 * 添加ACL权限管理系统
 */
class AddAclSystem extends Migrator
{
    /**
     * @var string ACL中的角色表
     */
    protected $tableRoles = 'acl_role';
    /**
     * @var string ACL中的资源表
     */
    protected $tableResources = 'acl_resource';
    /**
     * @var string ACL中的资源映射
     */
    protected $tableAcl = 'acl_user_acl_grant';
    /**
     * @var string ACL角色的父角色映射
     */
    protected $tableRoleParents = 'acl_role_parent';


    public function up()
    {
        $this->createRoleTable();
        $this->createRoleParentTable();
        $this->createResourceTable();
        $this->createGrantTable();
    }

    public function down()
    {
        $this->dropGrantTable();
        $this->dropResourceTable();
        $this->dropRoleParentTable();
        $this->dropRoleTable();
    }


    protected function createRoleParentTable()
    {
        if (!$this->hasTable($this->tableRoleParents)) {
            $table = $this->table($this->tableRoleParents, ['engine' => 'innodb', 'comment' => '角色父子关系表']);
            $table->addColumn(Column::integer('role_id')->setComment('关联的角色ID'))
                ->addColumn(Column::integer('pid')->setComment('关联的父ID'))
                ->addColumn(Column::integer('create_at')->setComment('记录创建时间')->setUnsigned())
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间')->setUnsigned())
                ->addIndex(['role_id', 'pid'], ['name' => 'unique_map', 'unique' => true])
                ->addForeignKey('role_id', $this->tableRoles, 'id')
                ->addForeignKey('pid', $this->tableRoles, 'id')
                ->create();
            $this->prepareRoleParentsTable();
        }
    }

    protected function dropRoleParentTable()
    {
        if ($this->hasTable($this->tableRoleParents)) {
            $this->dropTable($this->tableRoleParents);
        }
    }

    protected function createGrantTable()
    {
        if (!$this->hasTable($this->tableAcl)) {
            $table = $this->table($this->tableAcl, ['engine' => 'innodb', 'comment' => '权限授权表']);
            $table->addColumn(Column::integer('role_id')->setComment('关联的角色ID'))
                ->addColumn(Column::integer('resource_id')->setComment('关联的资源ID')->setNullable())
                ->addColumn(Column::string('privileges')->setComment('资源相关的权限信息，以英文逗号分隔')->setDefault(''))
                ->addColumn(Column::boolean('granted')->setComment('是否授权：0拒绝1授权'))
                ->addColumn(Column::integer('create_at')->setComment('记录创建时间')->setUnsigned())
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间')->setUnsigned())
                ->addForeignKey('role_id', $this->tableRoles, 'id')
                ->addForeignKey('resource_id', $this->tableResources, 'id')
                ->create();
            $this->prepareGrantTable();
        }
    }

    protected function prepareGrantTable()
    {
        //这里为匿名用户添加访问权限
        //为普通会员提供
    }

    protected function dropGrantTable()
    {
        if ($this->hasTable($this->tableAcl)) {
            $this->dropTable($this->tableAcl);
        }
    }

    protected function createResourceTable()
    {
        //此表只有特殊的情况下才会有，一般情况下不是十分需要
        if (!$this->hasTable($this->tableResources)) {
            $table = $this->table($this->tableResources, ['engine' => 'innodb', 'comment' => '权限资源表']);
            $table->addColumn(Column::string('resource_type', 20)->setComment('资源类型'))
                ->addColumn(Column::string('resource_id')->setComment('资源ID'))
                ->addColumn(Column::integer('pid')->setComment('资源的父ID')->setNullable())
                ->addColumn(Column::string('title', 20)->setComment('资源名称'))
                ->addColumn(Column::string('description')->setComment('资源描述'))
                ->addColumn(Column::integer('create_at')->setComment('记录创建时间')->setUnsigned())
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间')->setUnsigned())
                ->addIndex(['resource_type', 'resource_id'], ['name' => 'unique_resource', 'unique' => true])
                ->addForeignKey('pid', $this->tableResources, 'id')
                ->create();
        }
    }

    protected function dropResourceTable()
    {
        if ($this->hasTable($this->tableResources)) {
            $this->dropTable($this->tableResources);
        }
    }

    protected function createRoleTable()
    {
        if (!$this->hasTable($this->tableRoles)) {
            $table = $this->table($this->tableRoles, ['engine' => 'innodb', 'comment' => '权限角色表']);
            $table->addColumn(Column::string('code', 40)->setUnique()->setComment('角色机读码'))
                ->addColumn(Column::string('title', 64)->setComment('角色名称'))
                ->addColumn(Column::string('description')->setComment('角色的简短介绍'))
                ->addColumn(Column::integer('create_at')->setComment('记录创建时间')->setUnsigned())
                ->addColumn(Column::integer('update_at')->setComment('记录修改时间')->setUnsigned())
                ->create();
            $this->prepareRolesTable();
        }
    }

    protected function dropRoleTable()
    {
        if ($this->hasTable($this->tableRoles)) {
            $this->dropTable($this->tableRoles);
        }
    }

    protected function prepareRoleParentsTable()
    {
        //这里我们需要将internal_experience归纳到member角色中
        $map = [
            ['code' => 'internal_experience', 'parents' => ['member']],
        ];
        $adapter = $this->getAdapter();
        $tmpl = 'SELECT id FROM %s WHERE ' . $adapter->quoteColumnName('code') . ' = "%s" limit 1';
        $save = [];
        $now = time();
        $table = $this->table($this->tableRoles);
        foreach ($map as $item) {
            $target = $this->fetchRow(sprintf($tmpl, $adapter->quoteTableName($table->getName()), $item['code']));
            if (!$target) continue;
            foreach ($item['parents'] as $parent) {
                $p = $this->fetchRow(sprintf($tmpl, $adapter->quoteTableName($table->getName()), $parent));
                if (!$p) continue;
                //现在添加数据
                $save[] = ['role_id' => $target['id'], 'pid' => $p['id'], 'create_at' => $now, 'update_at' => $now];
            }
        }
        $this->table($this->tableRoleParents)->insert($save)->saveData();
    }

    protected function prepareRolesTable()
    {
        $roles = [
            ['code' => 'guest', 'title' => '匿名用户', 'description' => '访问系统的匿名用户'],
            ['code' => 'member', 'title' => '普通用户', 'description' => '前端用户的基础角色'],
            ['code' => 'super_admin', 'title' => '超管用户', 'description' => '后台超管用户'],
            ['code' => 'internal_experience', 'title' => '前端功能内部体验者', 'description' => '用于体验系统新开发功能的用户角色，谨记：只能是前端功能'],
        ];
        $roles = array_map(function ($item) {
            $item['create_at'] = $item['update_at'] = time();
            return $item;
        }, $roles);
        $table = $this->table($this->tableRoles);
        $table->insert($roles)->save();
    }
}
