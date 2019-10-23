<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\menu;


use think\Config;
use think\Request;
use think\Route;
use think\Url;

class MenuLink
{
    protected $link;
    protected $isExternal = false;
    protected $extra = [];

    public function __construct($url, $isExternal)
    {
        $this->link = $url;
        $this->isExternal = $isExternal;
    }

    /**
     * 创建外部链接
     * @param $url string
     * @return MenuLink
     */
    public static function createExternalLink($url)
    {
        return new static($url, true);
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return bool
     */
    public function isExternal()
    {
        return $this->isExternal;
    }

    /**
     * 创建内部链接
     * 系统会为链接提供变量服务，其规则是
     * <ul>
     * <li>1.如果提供的是整数型索引</li>
     * <ul>
     * <li>在这种情况下如果数组值以"%"开头则会调用left_to_arg left_to_load函数来返回变量数据，其中的left表明值剩余部分的字符串</li>
     * <li>剩余情况，则会获取当前请求中的指定键数据</li>
     * </ul>
     * <li>2.如果提供了字符串索引</li>
     * <ul>
     * <li>在这种情况下，如果数值以":"开头，则会获取当前请求中":"后面字符串所表示的参数</li>
     * <li>其他情况下将会返回一个键值对，其中的值就是设置的静态值</li>
     * </ul>
     * </ul>
     * @param $routeName
     * @param array $routeParameters
     * @param bool $suffix
     * @param bool $domain
     * @return MenuLink
     */
    public static function createInternalLink($routeName, $routeParameters = [], $suffix = true, $domain = false)
    {
        $vars = [];
        $request = Request::instance();
        foreach ($routeParameters as $key => $value) {
            if (is_integer($key)) {
                //当键是整数时
                if (strpos($value, '%') === 0) {//动态加载请求参数
                    $callback_arg = substr($value, 1) . '_to_arg';
                    if (is_callable($callback_arg)) $arguments = call_user_func($callback_arg);
                    else $arguments = [];
                    $callback_load = substr($value, 1) . '_load';
                    if (is_callable($callback_load)) $item_vars = call_user_func_array($callback_load, $arguments);
                    else $item_vars = [];
                    if (!empty($item_vars)) $vars = array_merge($vars, $item_vars);
                } else {
                    //单纯地获取请求参数
                    $vars[$value] = $request->param($value);
                }
            } else {
                //如果使用了映射
                if (strpos($value, ':') === 0) {
                    $vars[$key] = $request->param(substr($value, 1));
                } else {
                    $vars[$key] = $value;
                }
            }
        }
        $url = Url::build($routeName, $vars, $suffix, $domain);
        $object = new static($url, false);
        $object->setExtra([
            'route name' => $routeName,
            'route parameters' => $vars,
        ]);
        return $object;
    }

    public function setExtra(array $extra)
    {
        $this->extra = $extra;
    }

    public function hasLink()
    {
        return !empty($this->link);
    }

    public function isActiveTrail()
    {
        if ($this->isExternal) return false;
        if (empty($this->link)) return false;
        $request = Request::instance();
        $depr = Config::get('pathinfo_depr');
        $route_check = Route::check($request, $this->convertMatchLink(), $depr);
        if($route_check !== false) {
            $dispatch = $request->dispatch();
            return $dispatch === $route_check;
        }
        return false !== $route_check;
        //dump(); dump($request->path()); dump($this->convertMatchLink()); dump($this); exit;
    }

    protected function convertMatchLink($depr = '/')
    {
        $suffix = Config::get('url_html_suffix');
        if (false === $suffix) {
            // 禁止伪静态访问
            $return = $this->link;
        } elseif ($suffix) {
            // 去除正常的URL后缀
            $return  = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $this->link);
        } else {
            // 允许任何后缀访问
            $return = preg_replace('/\.' . pathinfo($this->link, PATHINFO_EXTENSION) . '$/i', '', $this->link);
        }
        return ltrim($return, $depr);
    }
}