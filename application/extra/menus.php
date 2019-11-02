<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * 后台菜单配置
 *    'home' => [
 *       'name' => '首页',                // 菜单名称
 *       'icon' => 'icon-home',          // 图标 (class)
 *       'manager' => 'manager/manager',         // 链接
 *     ],
 */
return [
    'backend.menu_container' => [
        'title' => '后台菜单容器',
        'menu_name' => 'admin_panel',
    ],
    'backend.index' => [
        'title' => '首页',
        'icon' => 'icon-home',
        'route name' => 'backend.home',
        'parent' => 'backend.menu_container',
        'weight' => -1,
    ],
    'backend.admin' => [
        'title' => '管理员',
        'icon' => 'icon-guanliyuan',
        'route name' => 'backend.admin_index',
        'parent' => 'backend.menu_container',
    ],
    'backend.basic' => [
        'title' => '基础管理',
        'icon' => 'icon-wxapp',
        //'route name' => 'backend.authorize_index',
        'parent' => 'backend.menu_container',
        'default link' => 'backend.basic_authorize',
    ],
    'backend.basic_authorize' => [
        'title' => '角色管理',
        'route name' => 'backend.authorize_index',
        'parent' => 'backend.basic',
        'weight' => -5,
    ],
    'backend.basic_authorize_res' => [
        'title' => '权限资源管理',
        'route name' => 'backend.authorize_res',
        'parent' => 'backend.basic',
    ],
    'backend.basic_authorize_rule' => [
        'title' => '权限规则管理',
        'route name' => 'backend.authorize_rule',
        'parent' => 'backend.basic',
        'weight' => 16,
    ],
    'backend.basic_authorize_price' => [
        'title' => '角色转化规则',
        'route name' => 'backend.authorize_price',
        'parent' => 'backend.basic',
        'weight' => 6,
    ],
];