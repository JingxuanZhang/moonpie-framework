<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/23
 * Time: 17:00
 */

namespace app\common\model;


use think\Cache;
use think\exception\DbException;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\RoleInterface;

class AclRole extends BaseModel implements RoleInterface
{
    public static function getDefaultRid()
    {
        $rid = self::where('code', 'member')->value('id');
        return $rid ? $rid : null;
    }
    public static function addRolesTo(Acl $manager)
    {
        //这里应该按照顺序处理
        $cache_key = 'all_acl_roles';
        $cache = Cache::get($cache_key, false);
        $cache = false;
        if(false === $cache) {
            $return = [];
            do {
                $query = static::alias('r');
                if(!isset($ids)) {
                    $query->whereNull('p.pid');
                    $query->join('__ACL_ROLE_PARENT__ p', 'p.role_id = r.id', 'LEFT');
                } else {
                    $query->join('__ACL_ROLE_PARENT__ p', 'p.role_id = r.id');
                    $query->whereIn('p.pid', $ids);
                }
                //$roles = $query->cache('all_acl_roles')->select();
                $ids = $query->column('distinct(r.id)');
                if(!empty($ids)) $return[] = $ids;
            }while(!empty($ids));
            $final = [];
            foreach($return as $level => $items) {
                foreach($items as $id) {
                    $final[$id] = $level;
                }
            }
            asort($final, SORT_ASC);
            $cache =array_keys($final);
            Cache::set($cache_key, $cache);
        }
        if(!$cache) return false;
        foreach ($cache as $id) {
            $role = static::where('id', $id)->cache("acl_role:{$id}")->find();
            $parents = $role->getParents();
            $manager->addRole($role, $parents);
        }
        return true;
    }

    public function getRoleId()
    {
        return $this->getData('code');
    }

    public function getParents()
    {
        $parents = $this->parents;
        if (empty($parents)) return null;
        return $parents;
    }

    public function parents()
    {
        return $this->belongsToMany(self::class, 'acl_role_parent', 'pid', 'role_id');
    }

    protected function initialize()
    {
        parent::initialize();
        static::afterDelete(function ($item) {
            Cache::rm('all_acl_roles');
            Cache::rm("acl_role:{$item['id']}");
        });
        static::afterWrite(function ($item) {
            Cache::rm('all_acl_roles');
            Cache::rm("acl_role:{$item['id']}");
        });
    }
    public function delete()
    {
        $this->startTrans();
        try {
            //首先需要清除规则
            $result = false !== $this->rules()->delete();
            if (!$result) {
                $this->rollback();
                return false;
            }

            $result = false !== $this->parents()->sync([]);
            if (!$result) {
                $this->rollback();
                return false;
            }
            $result = parent::delete();
            if(false === $result) {
                $this->rollback();
                return false;
            }
            $this->commit();
            return $result;
        }catch (DbException $e) {
            $this->rollback();
            return false;
        }
    }
    public function rules()
    {
        return $this->hasMany(AclUserAclGrant::class, 'role_id', 'id');
    }
}