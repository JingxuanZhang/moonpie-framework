<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\provider;


use app\common\service\security\AclManager;
use app\common\service\security\AssertionRegistry;
use app\common\service\security\ResourceRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use think\Hook;

class BasicProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        //提供ACL授权类
        $pimple['authorize'] = function ($c) {
            return new AclManager($c['logger']);
        };
        $pimple['authorize.acl_res_registry'] = function ($c) {
            $registry = new ResourceRegistry($c);
            $registry->add('\Zend\Permissions\Acl\Resource\GenericResource', '系统通用权限标识', '内置于系统中比较简单的基础资源类', true);
            $params = compact($registry);
            Hook::listen('acl_init_res_registry', $params);
            return $registry;
        };
        $pimple['authorize.acl_assert_registry'] = function ($c) {
            $registry = new AssertionRegistry($c);
            $params = compact($registry);
            Hook::listen('acl_init_assertion_registry', $params);
            return $registry;
        };
    }
}