<?php


namespace app\common\service\controller\traits;

use EasyWeChat\Kernel\Support\Arr;
use think\Hook;
use think\Request;

/**
 * 用来帮助开发者简化底部菜单功能的trait
 * @package app\common\service\controller\traits
 * @property Request $request
 * @method assign($name = '', $value = null) \think\Controller
 */
trait FooterMenu
{
    protected function init_menus()
    {
        $configs = (array)config('footer_menus.rules');
        $user = null;
        $params = ['default' => $configs, 'request' => $this->request, 'user' => $user, 'config' => $configs];
        Hook::listen('footer_menu_init', $params);
        $configs = $params['config'];
        $footerMenus = [];
        //现在判断是否是当前页面
        $info = $this->request->routeInfo();
        $menu_index = Arr::get($info, 'option._footer_menu.index', null);
        foreach ($configs as $idx => $config) {
            if ($config['is_hide']) continue;
            $tmp = $config;
            $tmp['current_no'] = sprintf('%02d', $tmp['sort']);
            //现在判断是否是当前页面
            if (!isset($tmp['is_active']) && !empty($menu_index)) {
                $tmp['is_active'] = $menu_index == $idx;
            }
            $footerMenus[] = $tmp;
        }
        $this->assign('footer_menus', $footerMenus);
    }
}