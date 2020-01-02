<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */


namespace app\common\service\plugin;


use app\common\service\App;
use app\common\service\Console;
use app\common\service\helper\ArrayHelper;
use app\common\service\ServiceContainer;
use Drupal\Component\Graph\Graph;
use EasyWeChat\Kernel\Support\Arr;
use think\Cache;
use think\Config;
use think\Hook;
use think\Loader;
use think\Route;

class PluginManager
{
    protected $debug = false;
    protected $rootDirs = [];
    protected $plugins = [];
    protected $loadedPlugins = [];
    protected $pluginLoaded = false;
    protected $expectPluginCodes = [];
    const CACHE_KEY_PLUGIN_ELEMENTS = 'system:load-plugin-elements';
    const CACHE_KEY_PLUGIN_ENABLE = 'system:enable-plugin-elements';
    const SCOPE_INSTALL = 'install';
    const SCOPE_UNINSTALL = 'uninstall';
    const SCOPE_UPGRADE = 'upgrade';

    public function __construct(array $root, $debug = false)
    {
        $this->rootDirs = array_map(function ($dir) {
            return rtrim($dir, '\\/') . DS;
        }, $root);
        $this->loadEnableCache();
        $this->debug = (bool)$debug;
    }

    protected function loadEnableCache()
    {
        $cache_data = Cache::get(static::CACHE_KEY_PLUGIN_ENABLE, []);
        return $this->expectPluginCodes = array_keys($cache_data);
    }

    protected function init()
    {
        //这里主要是要加载插件信息
        $this->prepareElements();
        $this->loadPlugins();
    }

    protected function loadPlugins()
    {
        if (!$this->pluginLoaded) {
            $graph_data = [];
            /** @var PluginElement $plugin_element */
            foreach ($this->plugins as $plugin_element) {
                $depend_data = $plugin_element->getDependData();
                $graph_data[$plugin_element->getCode()] = [
                    'edges' => []
                ];
                foreach ($depend_data as $depend_datum) {
                    $graph_data[$plugin_element->getCode()]['edges'][$depend_datum['name']] = $depend_datum;
                }
            }
            $graph = new Graph($graph_data);
            $sort_data = $graph->searchAndSort();
            $enable_data = [];

            foreach ($sort_data as $plugin_code => $depend_data_loop) {
                $children_codes = isset($depend_data_loop['reverse_paths']) ? $depend_data_loop['reverse_paths'] : [];
                $parent_codes = isset($depend_data_loop['paths']) ? $depend_data_loop['paths'] : [];
                $this->plugins[$plugin_code]->setElement('required_by', $children_codes)
                    ->setElement('requires', $parent_codes)
                    ->setElement('weight', $depend_data_loop['weight']);
                if (in_array($plugin_code, $this->expectPluginCodes)) {
                    $this->plugins[$plugin_code]->setElement('enabled', true);
                    $enable_data[$plugin_code] = $this->plugins[$plugin_code]->getVersion();
                    //这里添加命名空间
                    $namespace = $this->plugins[$plugin_code]->getNamespace();
                    if (!empty($namespace)) {
                        Loader::addNamespace($namespace, $this->plugins[$plugin_code]->getRootPath());
                    }
                } else if ($this->plugins[$plugin_code]->getElement('enabled', false) === false) {
                    $this->plugins[$plugin_code]->setElement('enabled', false);
                } else {
                    //这里添加命名空间
                    $namespace = $this->plugins[$plugin_code]->getNamespace();
                    if (!empty($namespace)) {
                        Loader::addNamespace($namespace, $this->plugins[$plugin_code]->getRootPath());
                    }
                    $enable_data[$plugin_code] = $this->plugins[$plugin_code]->getVersion();
                }
            }
            $enable_cache = Cache::get(static::CACHE_KEY_PLUGIN_ENABLE, false);
            if (false === $enable_cache) Cache::set(static::CACHE_KEY_PLUGIN_ENABLE, $enable_data);
            $this->pluginLoaded = true;
        }
    }

    /**
     * 准备信息
     */
    protected function prepareElements()
    {
        if (!$this->pluginLoaded) {
            $cache_key = static::CACHE_KEY_PLUGIN_ELEMENTS;
            $elements = Cache::get($cache_key, false);
            if (false === $elements || $this->debug) {
                $this->discoverPluginElements();
            } else {
                foreach ($elements as $prop => $element) {
                    $this->plugins[$element->getCode()] = $element;
                }
            }
            //var_dump($this->plugins);exit;
        }
    }

    /**
     * 定位所有可用的插件信息
     */
    protected function discoverPluginElements()
    {
        foreach ($this->rootDirs as $root) {
            $iterator = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
            $filter = new \RecursiveCallbackFilterIterator($iterator, function ($current_item, $current_key, $iterator) {
                /** @var \SplFileInfo $current_item */
                if ($iterator->hasChildren()) return true;
                if ($current_item->isFile() && $current_item->getBasename() == 'manifest.xml') return true;
                return false;
            });
            $outer = new \RecursiveIteratorIterator($filter);
            /** @var \SplFileInfo $file */
            foreach ($outer as $file) {
                $element = PluginElement::loadFromXmlFile($file);
                if ($element) $this->plugins[$element->getCode()] = $element;
            }
        }
        !$this->debug && Cache::set(static::CACHE_KEY_PLUGIN_ELEMENTS, $this->plugins);
    }

    public function reset()
    {
        $this->loadedPlugins = [];
        $this->plugins = [];
        $this->pluginLoaded = false;
    }

    public function registerService(ServiceContainer $container)
    {
        $this->init();
        /** @var PluginElement $plugin_element */
        foreach ($this->plugins as $plugin_element) {
            if (!$plugin_element->isEnable()) continue;
            $class_names = $plugin_element->getServiceProviderClasses();
            foreach ($class_names as $class_name) {
                $container->registerByClassName($class_name);
            }
        }
    }

    /**
     * 引入插件的配置信息
     * @param $scope string 配置节点
     * @param string $module 适配的模块
     * @return array 稍后需要处理的数据
     */
    public function importPluginConfig($scope, $module = '')
    {
        $this->init();
        $params = [];
        $hook_pool = [];
        /** @var PluginElement $plugin_element */
        foreach ($this->plugins as $plugin_element) {
            if (!$plugin_element->isEnable()) continue;
            switch ($scope) {
                case 'config':
                    $configs = $plugin_element->getElement('configs', []);
                    //首先加入配置
                    foreach ($configs as $config) {
                        if (ArrayHelper::has($config, 'file')) {
                            $file_path = ArrayHelper::getValue($config, 'file');
                            $real_path = $plugin_element->getRootPath() . $file_path;
                            if (file_exists($real_path)) {
                                $range_name = ArrayHelper::getValue($config, 'range', 'plugin');
                                $range_name = $range_name == 'default' ? '' : $range_name;
                                $config_name = ArrayHelper::getValue($config, 'name', '');
                                if (empty($config_name)) {//如果没有填写，默认是插件自己的配置
                                    $config_name = $plugin_element->getCode();
                                } else if ($config_name == 'global') {
                                    $config_name = null;//如果是全局配置，那么设为null
                                    $range_name = '';
                                } else if ($range_name == 'plugin') {
                                    if(strpos($config_name, '.') === 0) {
                                        //如果需要声明插件内配置，需要在前面添加"."
                                        $config_name = "{$plugin_element->getCode()}{$config_name}";
                                    }
                                }
                                $strategy = ArrayHelper::getValue($config, 'strategy', 'override');
                                if ($strategy == 'override') {
                                    if (stripos($config_name, '.') === false) {
                                        Config::load($real_path, $config_name, $range_name);
                                    } else {
                                        Config::set($config_name, include $real_path, $range_name);
                                    }
                                } else {
                                    $params[] = compact('strategy', 'real_path', 'config_name', 'range_name', 'plugin_element');
                                }
                                //Config::set($config_name, include $real_path, $range_name);
                            } else if (App::$debug) {
                                throw new \RuntimeException(sprintf('plugin(code: %s) cannot load its config',
                                    $plugin_element->getCode()));
                            }
                        }
                    }
                    if (empty($module)) {
                        //之后是钩子
                        $hooks = $plugin_element->getElement('hooks', []);
                        $hook_pool = array_merge($hook_pool, $hooks);

                        //还有自定义函数
                        $helpers = $plugin_element->getElement('helpers', []);
                        $base_path = $plugin_element->getRootPath();
                        foreach ($helpers as $helper) {
                            $helper_path = $base_path . $helper['target'];
                            App::loadFile($helper_path);
                        }
                    }
                    break;
                case 'route':
                    $routes = $plugin_element->getElement('routes', []);
                    $base_path = $plugin_element->getRootPath();
                    foreach ($routes as $route) {
                        $route_path = $base_path . $route;
                        $rules = include $route_path;
                        if (is_array($rules)) Route::import($rules);
                    }
                    break;
                case 'command':
                    $commands = $plugin_element->getElement('commands', []);
                    $console = Console::init(false);
                    $container = app(true);
                    foreach ($commands as $command) {
                        $command_object = $container->createFromDefinition($command);
                        if ($command_object) $console->add($command_object);
                    }
                    break;
            }
        }
        if (empty($module) && $scope == 'config' && !empty($hook_pool)) {
            //数字越大越靠前,倒序
            usort($hook_pool, function ($a, $b) {
                $a_weight = Arr::get($a, 'priority', 0);
                $b_weight = Arr::get($b, 'priority', 0);
                return $a_weight > $b_weight ? -1 : $a_weight == $b_weight ? 0 : 1;
            });
            foreach ($hook_pool as $hook) {
                if (isset($hook['method'])) {
                    $behavior = [$hook['class'], $hook['method']];
                } else {
                    $behavior = $hook['class'];
                }
                Hook::add($hook['tag'], $behavior);
            }
        }
        return $params;

    }

    public function upgrade(array $codes, $force = false)
    {
        $plugins = $this->checkAndSort($codes, static::SCOPE_UPGRADE, $force);
        $final = [];
        foreach ($plugins as $plugin) {
            $plugin->onUpgrade($this, $force);
            $final[] = $plugin;
        }
        return $final;
    }

    public function install(array $codes, $force = false)
    {
        $plugins = $this->checkAndSort($codes, static::SCOPE_INSTALL, $force);
        $final = [];
        foreach ($plugins as $plugin) {
            $plugin->onInstall($this, $force);
            $final[] = $plugin;
        }

        //最后标记插件安装成功
        $this->addPluginCache($final);
        return $final;
    }

    protected function addPluginCache($freshPlugins)
    {
        $cache_data = Cache::get(static::CACHE_KEY_PLUGIN_ELEMENTS, []);
        $cache_enable_data = Cache::get(static::CACHE_KEY_PLUGIN_ENABLE, []);
        foreach ($freshPlugins as $plugin) {
            $plugin->setElement('enabled', true);
            $cache_data[$plugin->getCode()] = $plugin;
            $cache_enable_data[$plugin->getCode()] = $plugin->getVersion();
        }
        Cache::set(static::CACHE_KEY_PLUGIN_ELEMENTS, $cache_data);
        Cache::set(static::CACHE_KEY_PLUGIN_ENABLE, $cache_enable_data);
        return $cache_data;
    }

    /**
     * 移除已安装的插件
     * @param $removePlugins
     * @return mixed
     */
    protected function removePluginCache($removePlugins)
    {
        $cache_data = Cache::get(static::CACHE_KEY_PLUGIN_ELEMENTS, []);
        $cache_enable_data = Cache::get(static::CACHE_KEY_PLUGIN_ENABLE, []);
        foreach ($removePlugins as $plugin) {
            $plugin->setElement('enabled', false);
            $cache_data[$plugin->getCode()] = $plugin;
            unset($cache_enable_data[$plugin->getCode()]);
        }
        Cache::set(static::CACHE_KEY_PLUGIN_ELEMENTS, $cache_data);
        Cache::set(static::CACHE_KEY_PLUGIN_ENABLE, $cache_enable_data);
        return $cache_data;
    }

    public function uninstall(array $codes, $force = false)
    {
        $plugins = $this->checkAndSort($codes, static::SCOPE_UNINSTALL, $force);
        $final = [];
        foreach ($plugins as $plugin) {
            $plugin->onUninstall($this, $force);
            $final[] = $plugin;
        }
        //最后标记插件删除成功
        $this->removePluginCache($final);
        return $final;
    }

    /**
     * 根据机读吗获取指定环境下可以操作的插件信息
     * @param array $codes 筛选的插件机读吗
     * @param $scope string 指定的环境
     * @param $force boolean 是否强制
     * @return PluginElement[]
     */
    protected function checkAndSort(array $codes, $scope, $force)
    {
        if ($force) $this->reset();
        $this->init();
        switch ($scope) {
            case static::SCOPE_INSTALL:
                $require_codes = [];
                foreach ($codes as $code) {
                    if (!array_key_exists($code, $this->plugins)) {
                        throw new exception\PluginNotFoundException($code, "指定的插件(code: {$code})不存在与文件系统中");
                    }
                    $plugin_element = $this->plugins[$code];
                    $require_codes[] = $plugin_element;
                    $requires = $plugin_element->getElement('requires', []);
                    foreach ($requires as $require_code => $require_info) {
                        if (!array_key_exists($require_code, $this->plugins)) {
                            throw new exception\PluginDependencyException($plugin_element, $require_info, sprintf('插件(code: %s)依赖的插件(code: %s)并不存在',
                                $plugin_element->getCode(), $require_code));
                        }
                        $parent_element = $this->plugins[$require_code];
                        $require_codes[] = $parent_element;
                    }
                }
                //现在完整的信息已经有了，过滤已经安装的
                $require_elements = array_filter($require_codes, function ($element) use ($force) {
                    if ($force) return true;
                    return !$element->isEnable();
                });
                //现在可以排序了
                usort($require_elements, function ($a, $b) {
                    $a_weight = $a->getElement('weight', 0);
                    $b_weight = $b->getElement('weight', 0);
                    return $a_weight > $b_weight ? 1 : $a_weight < $b_weight ? -1 : 0;
                });
                return $require_elements;
            case static::SCOPE_UPGRADE:
                $require_codes = [];
                foreach ($codes as $code) {
                    if (!array_key_exists($code, $this->plugins)) {
                        throw new exception\PluginNotFoundException($code, "指定的插件(code: {$code})不存在与文件系统中");
                    }
                    $plugin_element = $this->plugins[$code];
                    $require_codes[] = $plugin_element;
                    $requires = $plugin_element->getElement('requires', []);
                    foreach ($requires as $require_code => $require_info) {
                        if (!array_key_exists($require_code, $this->plugins)) {
                            throw new exception\PluginDependencyException($plugin_element, $require_info, sprintf('插件(code: %s)依赖的插件(code: %s)并不存在',
                                $plugin_element->getCode(), $require_code));
                        }
                        $parent_element = $this->plugins[$require_code];
                        $require_codes[] = $parent_element;
                    }
                }
                //现在完整的信息已经有了，过滤已经安装的
                $require_elements = array_filter($require_codes, function ($element) use ($force) {
                    if ($force) return true;
                    return $element->isEnable();
                });
                //现在可以排序了
                usort($require_elements, function ($a, $b) {
                    $a_weight = $a->getElement('weight', 0);
                    $b_weight = $b->getElement('weight', 0);
                    return $a_weight > $b_weight ? 1 : $a_weight < $b_weight ? -1 : 0;
                });
                return $require_elements;
            case static::SCOPE_UNINSTALL:
                $require_codes = [];
                foreach ($codes as $code) {
                    if (!array_key_exists($code, $this->plugins)) {
                        throw new exception\PluginNotFoundException($code, "指定的插件(code: {$code})不存在与文件系统中");
                    }
                    $plugin_element = $this->plugins[$code];
                    $require_codes[] = $plugin_element;
                    $requires = $plugin_element->getElement('required_by', []);
                    foreach ($requires as $require_code => $require_info) {
                        if (array_key_exists($require_code, $this->plugins)) {
                            $child_element = $this->plugins[$require_code];
                            $require_codes[] = $child_element;
                        }
                    }
                }
                //现在完整的信息已经有了，过滤已经安装的
                $require_elements = array_filter($require_codes, function ($element) use ($force) {
                    if ($force) return true;
                    return $element->isEnable();
                });
                //现在可以排序了
                usort($require_elements, function ($a, $b) {
                    $a_weight = $a->getElement('weight', 0);
                    $b_weight = $b->getElement('weight', 0);
                    return $a_weight > $b_weight ? 1 : $a_weight < $b_weight ? -1 : 0;
                });
                return $require_elements;
            default:
                throw new exception\UnSupportedOperationException($scope, "暂未支持的操作:{$scope}");
        }
    }

    public function refreshPluginCache()
    {
        return false !== Cache::rm(static::CACHE_KEY_PLUGIN_ELEMENTS);
    }

    public function getPluginByCode($code, $checkValid = true)
    {
        $this->init();
        if (!isset($this->plugins[$code])) return false;
        $plugin = $this->plugins[$code];
        if ($checkValid && !$plugin->isEnable()) return false;
        return $plugin;
    }

    public function getPlugins($checkValid = true)
    {
        $this->init();
        if ($checkValid) {
            return array_filter($this->plugins, function ($plugin) {
                return $plugin->isEnable();
            });
        }
        return $this->plugins;
    }
}