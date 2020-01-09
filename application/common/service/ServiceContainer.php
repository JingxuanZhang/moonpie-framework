<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service;

use app\common\model\Setting;
use app\common\service\base\Collection;
use app\common\service\base\ConfigResolver;
use app\common\service\container\BaseServiceContainer;
use app\common\service\container\RewindableGenerator;
use app\common\service\container\ServiceProviderInterface;
use app\common\service\filesystem\control\DefaultControlEngine;
use app\common\service\filesystem\FilesystemFactory;
use app\common\service\filesystem\FilesystemManager;
use app\common\service\filesystem\VisitManager;
use app\common\service\lock\LockFactory;
use app\common\service\log\LogManager;
use app\common\service\log\ThinkDbHandler;
use app\common\service\menu\MenuService;
use app\common\service\plugin\asset\AssetManager;
use app\common\service\plugin\PluginManager;
use app\common\service\resolver\ClassResolver;
use app\common\service\routing\Router;
use Monolog\Logger;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use think\Config;
use think\Env;

class ServiceContainer extends BaseServiceContainer
{
    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  string  $tag
     * @return iterable
     */
    public function tagged($tag)
    {
        if (! isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract => $attributes) {
                yield $abstract => [$this->make($abstract), $attributes];
            }
        }, count($this->tags[$tag]));
    }

    public function __construct()
    {
        $this->prepareBase();
    }

    public function registerFromConfig()
    {
        $providers = Config::get('providers');
        if (is_array($providers) && !empty($providers)) {
            foreach ($providers as $provider) {
                $this->registerByClassName($provider);
            }
        }
    }

    public function registerByClassName($className)
    {
        try {
            $reflector = new \ReflectionClass($className);
            if ($reflector->implementsInterface(ServiceProviderInterface::class)) {
                /** @var ServiceProviderInterface $class */
                $class = $reflector->newInstance();
                $class->register($this);
            }
        } catch (\ReflectionException $e) { }
    }

    protected function prepareBase()
    {
        //首先注册Lock
        //信号存储
        $this->singleton('lock.store.semaphore'  , function () {
            return new SemaphoreStore();
        });

        $this->singleton('config'  , function ($c) {
            return new Collection(Config::get());
        });
        //flock
        $this->singleton('lock.store.flock',   function ($c) {
            $env = $c->get('app_env');
            $dir = RUNTIME_PATH . 'lock' . DS . $env['env'];
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return new FlockStore($dir);
        });
        //先使用本地的
        $this->singleton('lock.factory'  , function ($c) {
            $factory = new LockFactory();
            $factory->setServiceContainer($c);
            return $factory;
        });
        $this->singleton('lock.factory.default'  , function ($c) {
            $store = $c['lock.store.flock'];
            $factory = new Factory($store);
            return $factory;
        });
        $this->singleton('logger'  , function ($c) {
            $logger = new LogManager($c);
            $logger->extend('db', function ($app, $config) use ($logger) {
                $handler = new ThinkDbHandler('sys_log', Logger::toMonologLevel($config['level'] ? $config['level'] : 'debug'));
                return new Logger($config['name'], [$handler]);
            });
            return $logger;
        });
        $this->instance('app_src_paths',   [
            'app' => APP_PATH,
            'plugin' => ROOT_PATH . 'plugin' . DS,
        ]);
        /*$this['enable_plugins'] = function ($c) {
            return ['manager'];
        };*/
        $this->instance('app_env', [
            'debug' => App::$debug,
            'env' => Env::get('app_status', 'prod')
        ]);
        $this->singleton('plugin.manager',   function ($c) {
            $root_paths = $c['app_src_paths'];
            $debug = $c['app_env']['debug'];
            //$valid_plugins = $c['enable_plugins'];
            //$plugin_manager = new PluginManager($root_paths, $valid_plugins, $debug);
            $plugin_manager = new PluginManager($root_paths, $debug);
            //$plugin_manager->registerService($this);
            return $plugin_manager;
        });
        //菜单部分
        $this->singleton('menu.manager'  , function ($c) {
            return new MenuService($c['plugin.manager']);
        });
        //资源部分
        $this->singleton('asset.manager'  , function ($c) {
            return new AssetManager($c['plugin.manager']);
        });
        //类解决器
        $this->singleton('class.resolver'  , function ($c) {
            return new ClassResolver($c);
        });
        //新增存储相关
        $this->singleton('storage.factory'  , function ($c) {
            return new FilesystemFactory();
        });
        $this->singleton('storage.visit_manager' , function ($c) {
            return new VisitManager;
        });
        $this->singleton('storage.manager'  , function ($c) {
            return new FilesystemManager($c['storage.factory'], $c['storage.visit_manager'], Setting::getItem('storage'));
        });
        $this->singleton('storage.engine_manager'  , function ($c) {
            //添加默认的引擎管理器
            $return = new DefaultControlEngine();
            $services = $c->tagged('storage.control_handler');
            foreach($services as $service_id => $params) {
                list($tag_service, $attributes) = $params;
                $return->addEngineHandler($tag_service, isset($attributes['priority']) ? $attributes['priority'] : 0);
            }
            return $return;
        });
        //基础配置部分
        $this->singleton('config.resolver'  , function($c){
            return new ConfigResolver();
        });
        //提供路由相关
        $this->singleton('routing.router'  , function($c){
            $router = new Router($c);
            $tag = 'routing.middleware_register';
            $services = $c->tagged($tag);
            foreach($services as $service_id => $params) {
                list($service, $attributes) = $params;
                $service->registerMiddleware($router);
            }
            return $router;
        });
    }
    public function createFromDefinition($definition, $arguments = [])
    {
        return $this->make($definition, $arguments);
    }

    /**
     * 添加服务标签
     * @param  string  $service
     * @param  string $tagName
     * @param array $tagAttributes
     * @return $this
     */
    public function tag($service, $tagName, $tagAttributes = [])
    {
        if (! isset($this->tags[$tagName])) {
            $this->tags[$tagName] = [];
        }
        $this->tags[$tagName][$service] = $tagAttributes;
        return $this;
    }
}
