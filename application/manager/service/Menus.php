<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\service;

use think\Config;
use think\Hook;

/**
 * 商家后台菜单业务
 * Class Menus
 * @package app\store\service
 */
class Menus
{
    /** @var self $instance 存放实例 */
    private static $instance;

    /** @var Auth $user 商家用户信息 */
    private $auth;

    /**
     * 公有化获取实例方法
     * @return Menus
     * @throws \think\exception\DbException
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof static)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * 私有化构造方法
     * Menus constructor.
     * @throws \think\exception\DbException
     */
    private function __construct()
    {
        // $this->auth = Auth::getInstance();
    }

    /**
     * 私有化克隆方法
     */
    private function __clone()
    {
    }

    /**
     * 后台菜单配置
     * @param $routeUri
     * @param $group
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenus($routeUri, $group)
    {
        // 菜单列表数据
        $menus = Config::get('menus');
        $params = compact('routeUri', 'group', 'menus');
        Hook::listen('alter_sys_menus', $params);
        $this->first($params['menus'], $params['routeUri'], $params['group']);
//        pre($menus);
        return $params['menus'];
    }
    public function getPluginMenus($plugin, $routeUri, $group)
    {
        // 菜单列表数据
        $menus = Config::get("{$plugin}.menus", 'plugin');
        $params = compact('routeUri', 'group', 'menus', 'plugin');
        Hook::listen('alter_plugin_menus', $params);
        $this->first($params['menus'], $params['routeUri'], $params['group']);
//        pre($menus);
        return $params['menus'];
    }

    /**
     * 一级菜单
     * @param $menus
     * @param $routeUri
     * @param $group
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function first(&$menus, $routeUri, $group)
    {
        foreach ($menus as $key => &$first) :
            // 一级菜单索引url
            $indexData = $this->getMenusIndexUrls($first, 1);
            // 权限验证
            $first['index'] = $this->getAuthUrl($indexData);
            if ($first['index'] === false) {
                unset($menus[$key]);
                continue;
            }
            // 菜单聚焦
            $first['active'] = $key === $group;
            //var_dump($first, $key, $group, $routeUri);
            // 遍历：二级菜单
            if (isset($first['submenu'])) {
                $this->second($first['submenu'], $routeUri);
            }
        endforeach;
    }

    /**
     * 二级菜单
     * @param menus
     * @param $routeUri
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function second(&$menus, $routeUri)
    {
        foreach ($menus as $key => &$second) :
            // 二级菜单索引url
            $indexData = $this->getMenusIndexUrls($second, 2);
            // 权限验证
            $second['index'] = $this->getAuthUrl($indexData);
            if ($second['index'] === false) {
                unset($menus[$key]);
                continue;
            }
            // 二级菜单所有uri
            $secondUris = [];
            // 遍历：三级菜单
            if (isset($second['submenu'])) {
                $this->third($second['submenu'], $routeUri, $secondUris);
            } else {
                if (isset($second['uris']))
                    $secondUris = array_merge($secondUris, $second['uris']);
                else
                    $secondUris[] = $second['index'];
            }
            // 二级菜单：active
            !isset($second['active']) && $second['active'] = in_array($routeUri, $secondUris);
        endforeach;
        // 删除空数组
        $menus = array_filter($menus);
    }

    /**
     * 三级菜单
     * @param $menus
     * @param $routeUri
     * @param $secondUris
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function third(&$menus, $routeUri, &$secondUris)
    {
        foreach ($menus as $key => &$third):
            // 三级菜单索引url
            $indexData = $this->getMenusIndexUrls($third, 3);
            // 权限验证
            $third['index'] = $this->getAuthUrl($indexData);
            if ($third['index'] === false) {
                unset($menus[$key]);
                continue;
            }
            // 三级菜单所有uri
            $thirdUris = [];
            if (isset($third['uris'])) {
                $secondUris = array_merge($secondUris, $third['uris']);
                $thirdUris = array_merge($thirdUris, $third['uris']);
            } else {
                $secondUris[] = $third['index'];
                $thirdUris[] = $third['index'];
            }
            $third['active'] = in_array($routeUri, $thirdUris);
        endforeach;
    }

    /**
     * 获取指定菜单下的所有索引url
     * @param $menus
     * @param int $level
     * @return array|null
     */
    private function getMenusIndexUrls(&$menus, $level = 1)
    {
        // 三级
        if ($level === 3) {
            return isset($menus['index']) ? [$menus['index']] : null;
        }
        // 一级和二级
        if (!isset($menus['index']) && !isset($menus['submenu'])) {
            return null;
        }
        $data = [];
        if (isset($menus['index']) && !empty($menus['index'])) {
            $data[] = $menus['index'];
        }
        if (isset($menus['submenu']) && !empty($menus['submenu'])) {
            foreach ($menus['submenu'] as $submenu) {
                $submenuIndex = $this->getMenusIndexUrls($submenu, ++$level);
                !is_null($submenuIndex) && $data = array_merge($data, $submenuIndex);
            }
        }
        return $data;
    }

    /**
     * 取出通过权限验证urk作为index
     * @param $urls
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getAuthUrl($urls)
    {
        // 取出通过权限验证urk作为index
        foreach ($urls as $url) {
            // if ($this->auth->checkPrivilege($url))
            return $url;
        }
        return false;
    }

}