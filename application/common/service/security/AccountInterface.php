<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\security;


use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

interface AccountInterface
{
    /**
     * @return RoleInterface 获取用户的角色信息
     */
    public function getRole();

    /**
     * 使用凭证信息登陆
     * @param array $credit
     * @param array $options
     * @return bool
     */
    public function login(array $credit, array $options = []);

    /**
     * 判断用户对指定资源是否有特定的权限
     * @param null $permission
     * @param ResourceInterface|string|null $resource
     * @return bool
     */
    public function hasPermission($permission = null, $resource = null);

    /**
     * 用户登出处理
     * @param array $extra
     * @return mixed
     */
    public function logout(array $extra = []);
}