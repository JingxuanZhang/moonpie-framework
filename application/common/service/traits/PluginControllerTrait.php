<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by PhpStorm.
 * User: johnzhang
 * Date: 2019/7/29 0029
 * Time: 下午 3:55
 */

namespace app\common\service\traits;

/**
 * 针对插件控制器的一些基础trait
 * Trait PluginControllerTrait
 * @package app\common\service\traits
 */
trait PluginControllerTrait
{
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $template = $this->parseTemplateName($template, $config);
        return parent::fetch($template, $vars, $replace, $config);
    }

    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        $content = $this->parseTemplateName($content, $config);
        return parent::display($content, $vars, $replace, $config);
    }

    protected function parseTemplateName($template, $config = [])
    {
        if (stripos($template, '#') === 0) {
            //这里我们解析以‘#’开头的语法，这表明是要解析到插件的
            $idx = stripos($template, '/');
            if (false !== $idx) {
                $plugin_code = substr($template, 1, $idx - 1);
            } else {
                $plugin_code = '';
                $idx = 0;
            }
            if (empty($plugin_code)) {
                //这里返回当前的视图路径
                $reflector = new \ReflectionClass($this);
                $filename = $reflector->getFileName();
                $prefix = substr($filename, 0, strpos($filename, 'controller'));
                $parse_tmpl = $prefix . 'view' . DS . ltrim(substr($template, $idx + 1), '\\/');


            } else {
                /** @var \app\common\service\plugin\PluginManager $plugin_manager */
                $plugin_manager = app('plugin.manager');
                $element = $plugin_manager->getPluginByCode($plugin_code);
                if (!$element) {
                }
                $parse_tmpl = $element->getRootPath() . 'view' . DS . ltrim(substr($template, $idx + 1), '\\/');
            }
            if ('' === pathinfo($parse_tmpl, PATHINFO_EXTENSION)) {
                $parse_tmpl .= '.' . ltrim($this->view->engine->config('view_suffix'), '.');
            }
            return $parse_tmpl;
        }
        return $template;
    }
}