<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\menu;

use app\common\service\plugin\PluginManager;
use think\Cache;
use think\Config;
use think\Hook;

/**
 * 处理菜单相关的服务
 * Class MenuService
 * @package app\common\service\menu
 */
class MenuService
{
    protected $pluginManager;
    private $menuCollection = [];
    private $collectDone = false;
    const CACHE_KEY_MENU_CACHE_NAME = 'system:menu-cache-key';

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function collectMenus($useCache = true)
    {
        if (!$this->collectDone) {
            if ($useCache) {
                $cache_data = Cache::get(static::CACHE_KEY_MENU_CACHE_NAME, false);
            } else {
                $cache_data = false;
            }
            if (false === $cache_data) {
                $source_local = Config::get('menus');
                $source_plugin = $this->collectFromPlugins();
                $cache_data = array_merge($source_plugin, $source_local);
                $params = ['menus' => $cache_data];
                Hook::listen('system.collect_menu_declares', $params);
                $cache_data = $params['menus'];
                array_walk($cache_data, [$this, 'prepareMenuDefaultKey']);
                if ($useCache) {
                    Cache::set(static::CACHE_KEY_MENU_CACHE_NAME, $cache_data);
                }
            }
            $this->collectDone = true;
            $this->menuCollection = $cache_data;
        }
        return $this->menuCollection;
    }

    protected function prepareMenuDefaultKey(&$definition, $menuKey)
    {
        if (!isset($definition['menu_name']) && !isset($definition['parent'])) {
            $definition['menu_name'] = '';
        }
        if (!isset($definition['weight'])) $definition['weight'] = 0;
    }

    /**
     * 获取插件相关的菜单声明信息
     * @return array
     */
    protected function collectFromPlugins()
    {
        $plugins = $this->pluginManager->getPlugins();
        $menus = [];
        foreach ($plugins as $plugin) {
            $menu = Config::get("{$plugin->getCode()}.menus", 'plugin');
            if (!empty($menu)) $menus = array_merge($menus, $menu);
        }
        return $menus;
    }

    public function getMenusByName($menuName, $base = null, $maxDepth = -1)
    {
        $this->collectMenus(false);
        $tree_data = [];
        $top_menus = array_filter($this->menuCollection, function ($definition) use ($menuName) {
            return isset($definition['menu_name']) && !isset($definition['parent']) && $definition['menu_name'] == $menuName;
        });
        $maxDepth = max(1, $maxDepth);
        $depth = 1;
        foreach ($top_menus as $top_key => $top_menu) {
            $top = $top_menu;
            $sub_data = $this->findSubMenuTrees($top, $top_key, $depth, $maxDepth);
            if (!empty($sub_data)) {
                uasort($sub_data, [$this, 'sortItem']);
                $top['sub_data'] = $sub_data;
            }
            $element = new MenuElement($top, $top_key);
            $element->calcActiveTrail();
            $tree_data[$top_key] = $element;
        }
        uasort($tree_data, [$this, 'sortItem']);
        return $tree_data;
    }
    protected function sortItem($a, $b) {
        $a_weight = $a->getWeight();
        $b_weight = $b->getWeight();
        if($a_weight == $b_weight) return 0;
        return $a_weight > $b_weight ? 1 : -1;
    }

    protected function findSubMenuTrees($parent, $key, $depth, $max)
    {
        $tree_data = [];
        if ($depth >= $max) return $tree_data;
        $menus = array_filter($this->menuCollection, function ($definition) use ($key) {
            return isset($definition['parent']) && $definition['parent'] == $key;
        });
        foreach ($menus as $menu_key => $definition) {
            $sub_data = $this->findSubMenuTrees($definition, $menu_key, $depth + 1, $max);
            if(!empty($sub_data)){
                uasort($sub_data, [$this, 'sortItem']);
                $definition['sub_data'] = $sub_data;
            }
            $tree_data[$menu_key] = new MenuElement($definition, $menu_key);
        }
        return $tree_data;
    }

    public function reset()
    {
        //@todo 重置菜单信息数据
    }
}