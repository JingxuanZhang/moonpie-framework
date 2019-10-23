<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\model;

use app\common\model\BaseModel;
use app\common\service\security\AccountInterface;
use think\Exception;
use think\Hook;
use think\Session;

class Admin extends BaseModel implements AccountInterface
{
    //
    protected $name = 'admin_user';

    public function getList()
    {
        return $this->paginate(15);
    }

    public function add($data)
    {
        if (self::checkExist($data['username'])) {
            $this->error = '用户名已存在';
            return false;
        }
        if ($data['password'] !== $data['password_confirm']) {
            $this->error = '确认密码不正确';
            return false;
        }
        try {
            $data['password'] = mp_hash($data['password']);
            $this->allowField(true)->save($data);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public static function checkExist($user_name)
    {
        return !!static::useGlobalScope(false)
            ->where('username', '=', $user_name)
            ->value('id');
    }


    public function login(array $credit, array $options = [])
    {
        if (!$user = $this->getLoginUser($credit['username'], $credit['password'])) {
            $this->error = '登录失败, 用户名或密码错误';
            return false;
        }
        // 保存登录状态
        $this->loginState($user);
        return true;
    }

    public function getLoginUser($username, $password)
    {
        return $this->where([
            'username' => $username,
            'password' => mp_hash($password)
        ])->find();
    }


    public function loginState($user)
    {
        Session::set(config('backend.session_name'), [
            'admin_user' => $user,
            'is_login' => true
        ]);
    }

    public function getRole()
    {
        return $this->ownRole;
    }
    public function ownRole()
    {
        return $this->hasOne(AclRole::class, 'id', 'role_id');
    }

    public function hasPermission($permission = null, $resource = null)
    {
        //第一个用户默认是超管
        if($this->getId() == 1) return true;
        /** @var \app\common\service\security\AclManager $manager */
        $manager = app('authorize');
        return $manager->isAllowed($this->getRole(), $resource, $permission);
    }

    public function logout(array $extra = [])
    {
        Session::delete(config('backend.session_name'));
        $params = ['account' => $this];
        Hook::listen('admin_user_login', $params);
    }
    public function changePassword($password)
    {
        return false !== $this->save(['password' => mp_hash($password)]);
    }
}
