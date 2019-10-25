<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service;

use app\common\service\pipeline\BasePipeLine;
use app\common\service\routing\Router;
use think\App as Base;
use think\Config;
use think\Env;
use think\exception\ClassNotFoundException;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\RouteNotFoundException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Log;
use think\Request;
use think\Response;
use think\Route;

class App extends Base
{
    protected static $routeFound = false;

    /**
     * 执行应用程序
     * @access public
     * @param Request $request 请求对象
     * @return Response
     * @throws Exception
     */
    public static function run(Request $request = null)
    {
        $request = is_null($request) ? Request::instance() : $request;

        try {
            $config = static::initCommon();

            // 模块/控制器绑定
            if (defined('BIND_MODULE')) {
                BIND_MODULE && Route::bind(BIND_MODULE);
            } elseif ($config['auto_bind_module']) {
                // 入口自动绑定
                $name = pathinfo($request->baseFile(), PATHINFO_FILENAME);
                if ($name && 'index' != $name && is_dir(APP_PATH . $name)) {
                    Route::bind($name);
                }
            }

            $request->filter($config['default_filter']);

            // 默认语言
            Lang::range($config['default_lang']);
            // 开启多语言机制 检测当前语言
            $config['lang_switch_on'] && Lang::detect();
            $request->langset(Lang::range());

            // 加载系统语言包
            Lang::load([
                THINK_PATH . 'lang' . DS . $request->langset() . EXT,
                APP_PATH . 'lang' . DS . $request->langset() . EXT,
            ]);

            /** @var Router $router */
            $router = app('routing.router');
            //这里添加中间件功能
            $data = (new BasePipeLine(app(true)))
                ->send($request)
                ->through($router->getGlobalMiddleware())
                ->then(static::dispatchForMiddleWares($config));
            if (1 > 2) {
                // 监听 app_dispatch
                Hook::listen('app_dispatch', static::$dispatch);
                // 获取应用调度信息
                $dispatch = static::$dispatch;

                // 未设置调度信息则进行 URL 路由检测
                if (empty($dispatch)) {
                    $dispatch = static::routeCheck($request, $config);
                }

                // 记录当前调度信息
                $request->dispatch($dispatch);

                // 记录路由和请求信息
                if (static::$debug) {
                    Log::record('[ ROUTE ] ' . var_export($dispatch, true), 'info');
                    Log::record('[ HEADER ] ' . var_export($request->header(), true), 'info');
                    Log::record('[ PARAM ] ' . var_export($request->param(), true), 'info');
                }

                // 监听 app_begin
                Hook::listen('app_begin', $dispatch);

                // 请求缓存检查
                $request->cache(
                    $config['request_cache'],
                    $config['request_cache_expire'],
                    $config['request_cache_except']
                );

                $data = static::exec($dispatch, $config);
            }
        } catch (HttpResponseException $exception) {
            $data = $exception->getResponse();
        }

        // 清空类的实例化
        Loader::clearInstance();

        // 输出数据到客户端
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $type = $request->isAjax() ?
                Config::get('default_ajax_return') :
                Config::get('default_return_type');

            $response = Response::create($data, $type);
        } else {
            $response = Response::create();
        }

        // 监听 app_end
        Hook::listen('app_end', $response);

        return $response;
    }

    protected static function dispatchForMiddleWares($config)
    {
        return function (Request $request) use ($config) {
            // 监听 app_dispatch
            Hook::listen('app_dispatch', static::$dispatch);
            // 获取应用调度信息
            $dispatch = static::$dispatch;

            // 未设置调度信息则进行 URL 路由检测
            if (empty($dispatch)) {
                $dispatch = static::routeCheck($request, $config);
            }
            if (static::$routeFound) {
                /** @var Router $router */
                $router = app('routing.router');
                return $router->dispatchToRequest($request, static::dispatchMiddlewareForRoute($request, $dispatch, $config));
                //如果使用了路由
                //dump($request->routeInfo());dump($request->route());exit;
            }

            return static::dispatchMiddlewareForRoute($request, $dispatch, $config)($request);
        };
    }

    protected static function dispatchMiddlewareForRoute(Request $request, $dispatch, $config)
    {
        return function (Request $request) use ($dispatch, $config) {
            // 记录当前调度信息
            $request->dispatch($dispatch);

            // 记录路由和请求信息
            if (static::$debug) {
                Log::record('[ ROUTE ] ' . var_export($dispatch, true), 'info');
                Log::record('[ HEADER ] ' . var_export($request->header(), true), 'info');
                Log::record('[ PARAM ] ' . var_export($request->param(), true), 'info');
            }

            // 监听 app_begin
            Hook::listen('app_begin', $dispatch);

            // 请求缓存检查
            $request->cache(
                $config['request_cache'],
                $config['request_cache_expire'],
                $config['request_cache_except']
            );
            return static::exec($dispatch, $config);
        };
    }

    /**
     * 初始化应用，并返回配置信息
     * @access public
     * @return array
     */
    public static function initCommon()
    {
        if (empty(static::$init)) {
            if (defined('APP_NAMESPACE')) {
                static::$namespace = APP_NAMESPACE;
            }

            Loader::addNamespace(static::$namespace, APP_PATH);

            // 初始化应用
            $config = static::init();
            static::$suffix = $config['class_suffix'];

            // 应用调试模式
            static::$debug = Env::get('app_debug', Config::get('app_debug'));

            if (!static::$debug) {
                ini_set('display_errors', 'Off');
            } elseif (!IS_CLI) {
                // 重新申请一块比较大的 buffer
                if (ob_get_level() > 0) {
                    $output = ob_get_clean();
                }

                ob_start();

                if (!empty($output)) {
                    echo $output;
                }

            }

            if (!empty($config['root_namespace'])) {
                Loader::addNamespace($config['root_namespace']);
            }

            // 加载额外文件
            if (!empty($config['extra_file_list'])) {
                foreach ($config['extra_file_list'] as $file) {
                    static::loadFile($file);
                }
            }

            // 设置系统时区
            date_default_timezone_set($config['default_timezone']);

            // 监听 app_init
            Hook::listen('app_init');

            static::$init = true;
        }

        return Config::get();
    }

    /**
     * 初始化应用或模块
     * @access public
     * @param string $module 模块名
     * @return array
     */
    private static function init($module = '')
    {
        // 定位模块目录
        $module = $module ? $module . DS : '';

        // 加载初始化文件
        if (is_file(APP_PATH . $module . 'init' . EXT)) {
            include APP_PATH . $module . 'init' . EXT;
        } elseif (is_file(RUNTIME_PATH . $module . 'init' . EXT)) {
            include RUNTIME_PATH . $module . 'init' . EXT;
        } else {
            // 加载公共文件
            $path = APP_PATH . $module;
            if (is_file($path . 'common' . EXT)) {
                include $path . 'common' . EXT;
            }
            // 加载模块配置
            $config = Config::load(CONF_PATH . $module . 'config' . CONF_EXT);

            // 读取数据库配置文件
            $filename = CONF_PATH . $module . 'database' . CONF_EXT;
            Config::load($filename, 'database');

            //这里我们开始加载插件的配置信息
            //如果是模块的话不应该再加载了
            if (empty($module)) {
                /** @var \app\common\service\plugin\PluginManager $plugin_manager */
                $plugin_manager = \app('plugin.manager');
                $params = $plugin_manager->importPluginConfig('config', $module);
            }

            // 读取扩展配置文件
            if (is_dir(CONF_PATH . $module . 'extra')) {
                $dir = CONF_PATH . $module . 'extra';
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ('.' . pathinfo($file, PATHINFO_EXTENSION) === CONF_EXT) {
                        $filename = $dir . DS . $file;
                        Config::load($filename, pathinfo($file, PATHINFO_FILENAME));
                    }
                }
            }

            // 加载应用状态配置
            if ($config['app_status']) {
                Config::load(CONF_PATH . $module . $config['app_status'] . CONF_EXT);
            }
            // 这里需要根据合并策略处理配置
            if(empty($module) && isset($params)) {
                //ConfigResolver::mergeConfigs($params);
                app('config.resolver')->mergeConfigs($params);
            }

            // 加载行为扩展文件
            if (is_file(CONF_PATH . $module . 'tags' . EXT)) {
                Hook::import(include CONF_PATH . $module . 'tags' . EXT);
            }
            //这里加载配置中的服务
            if (empty($module)) app(true)->registerFromConfig();

            // 加载当前模块语言包
            if ($module) {
                Lang::load($path . 'lang' . DS . Request::instance()->langset() . EXT);
            }
        }

        return Config::get();
    }

    /**
     * 设置当前请求的调度信息
     * @access public
     * @param array|string $dispatch 调度信息
     * @param string $type 调度类型
     * @return void
     */
    public static function dispatch($dispatch, $type = 'module')
    {
        static::$dispatch = ['type' => $type, $type => $dispatch];
    }

    /**
     * 执行函数或者闭包方法 支持参数调用
     * @access public
     * @param string|array|\Closure $function 函数或者闭包
     * @param array $vars 变量
     * @return mixed
     */
    public static function invokeFunction($function, $vars = [])
    {
        $reflect = new \ReflectionFunction($function);
        $args = static::bindParams($reflect, $vars);

        // 记录执行信息
        static::$debug && Log::record('[ RUN ] ' . $reflect->__toString(), 'info');

        return $reflect->invokeArgs($args);
    }

    /**
     * 调用反射执行类的方法 支持参数绑定
     * @access public
     * @param string|array $method 方法
     * @param array $vars 变量
     * @return mixed
     */
    public static function invokeMethod($method, $vars = [])
    {
        if (is_array($method)) {
            $class = is_object($method[0]) ? $method[0] : static::invokeClass($method[0]);
            $reflect = new \ReflectionMethod($class, $method[1]);
        } else {
            // 静态方法
            $reflect = new \ReflectionMethod($method);
        }

        $args = static::bindParams($reflect, $vars);

        static::$debug && Log::record('[ RUN ] ' . $reflect->class . '->' . $reflect->name . '[ ' . $reflect->getFileName() . ' ]', 'info');

        return $reflect->invokeArgs(isset($class) ? $class : null, $args);
    }

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @access public
     * @param string $class 类名
     * @param array $vars 变量
     * @return mixed
     */
    public static function invokeClass($class, $vars = [])
    {
        $reflect = new \ReflectionClass($class);
        $constructor = $reflect->getConstructor();
        $args = $constructor ? static::bindParams($constructor, $vars) : [];

        return $reflect->newInstanceArgs($args);
    }

    /**
     * 绑定参数
     * @access private
     * @param \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param array $vars 变量
     * @return array
     */
    private static function bindParams($reflect, $vars = [])
    {
        // 自动获取请求变量
        if (empty($vars)) {
            $vars = Config::get('url_param_type') ?
                Request::instance()->route() :
                Request::instance()->param();
        }

        $args = [];
        if ($reflect->getNumberOfParameters() > 0) {
            // 判断数组类型 数字数组时按顺序绑定参数
            reset($vars);
            $type = key($vars) === 0 ? 1 : 0;

            foreach ($reflect->getParameters() as $param) {
                $args[] = static::getParamValue($param, $vars, $type);
            }
        }

        return $args;
    }

    /**
     * 获取参数值
     * @access private
     * @param \ReflectionParameter $param 参数
     * @param array $vars 变量
     * @param string $type 类别
     * @return array
     */
    private static function getParamValue($param, &$vars, $type)
    {
        $name = $param->getName();
        $class = $param->getClass();

        if ($class) {
            $className = $class->getName();
            $bind = Request::instance()->$name;

            if ($bind instanceof $className) {
                $result = $bind;
            } else {
                if (method_exists($className, 'invoke')) {
                    $method = new \ReflectionMethod($className, 'invoke');

                    if ($method->isPublic() && $method->isStatic()) {
                        return $className::invoke(Request::instance());
                    }
                }

                $result = method_exists($className, 'instance') ?
                    $className::instance() :
                    new $className;
            }
        } elseif (1 == $type && !empty($vars)) {
            $result = array_shift($vars);
        } elseif (0 == $type && isset($vars[$name])) {
            $result = $vars[$name];
        } elseif ($param->isDefaultValueAvailable()) {
            $result = $param->getDefaultValue();
        } else {
            throw new \InvalidArgumentException('method param miss:' . $name);
        }

        return $result;
    }

    /**
     * 执行调用分发
     * @access protected
     * @param array $dispatch 调用信息
     * @param array $config 配置信息
     * @return Response|mixed
     * @throws \InvalidArgumentException
     */
    protected static function exec($dispatch, $config)
    {
        switch ($dispatch['type']) {
            case 'redirect': // 重定向跳转
                $data = Response::create($dispatch['url'], 'redirect')
                    ->code($dispatch['status']);
                break;
            case 'module': // 模块/控制器/操作
                $data = static::module(
                    $dispatch['module'],
                    $config,
                    isset($dispatch['convert']) ? $dispatch['convert'] : null
                );
                break;
            case 'controller': // 执行控制器操作
                $vars = array_merge(Request::instance()->param(), $dispatch['var']);
                $data = Loader::action(
                    $dispatch['controller'],
                    $vars,
                    $config['url_controller_layer'],
                    $config['controller_suffix']
                );
                break;
            case 'method': // 回调方法
                $vars = array_merge(Request::instance()->param(), $dispatch['var']);
                $data = static::invokeMethod($dispatch['method'], $vars);
                break;
            case 'function': // 闭包
                $data = static::invokeFunction($dispatch['function']);
                break;
            case 'response': // Response 实例
                $data = $dispatch['response'];
                break;
            default:
                throw new \InvalidArgumentException('dispatch type not support');
        }

        return $data;
    }

    /**
     * 执行模块
     * @access public
     * @param array $result 模块/控制器/操作
     * @param array $config 配置参数
     * @param bool $convert 是否自动转换控制器和操作名
     * @return mixed
     * @throws HttpException
     */
    public static function module($result, $config, $convert = null)
    {
        if (is_string($result)) {
            $result = explode('/', $result);
        }

        $request = Request::instance();

        if ($config['app_multi_module']) {
            // 多模块部署
            $module = strip_tags(strtolower($result[0] ?: $config['default_module']));
            $bind = Route::getBind('module');
            $available = false;

            if ($bind) {
                // 绑定模块
                list($bindModule) = explode('/', $bind);

                if (empty($result[0])) {
                    $module = $bindModule;
                    $available = true;
                } elseif ($module == $bindModule) {
                    $available = true;
                }
            } elseif (!in_array($module, $config['deny_module_list']) && is_dir(APP_PATH . $module)) {
                $available = true;
            }

            // 模块初始化
            if ($module && $available) {
                // 初始化模块
                $request->module($module);
                $config = static::init($module);

                // 模块请求缓存检查
                $request->cache(
                    $config['request_cache'],
                    $config['request_cache_expire'],
                    $config['request_cache_except']
                );
            } else {
                throw new HttpException(404, 'module not exists:' . $module);
            }
        } else {
            // 单一模块部署
            $module = '';
            $request->module($module);
        }

        // 设置默认过滤机制
        $request->filter($config['default_filter']);

        // 当前模块路径
        static::$modulePath = APP_PATH . ($module ? $module . DS : '');

        // 是否自动转换控制器和操作名
        $convert = is_bool($convert) ? $convert : $config['url_convert'];

        // 获取控制器名
        $controller = strip_tags($result[1] ?: $config['default_controller']);

        if (!preg_match('/^[A-Za-z](\w|\.)*$/', $controller)) {
            throw new HttpException(404, 'controller not exists:' . $controller);
        }

        $controller = $convert ? strtolower($controller) : $controller;

        // 获取操作名
        $actionName = strip_tags($result[2] ?: $config['default_action']);
        if (!empty($config['action_convert'])) {
            $actionName = Loader::parseName($actionName, 1);
        } else {
            $actionName = $convert ? strtolower($actionName) : $actionName;
        }

        // 设置当前请求的控制器、操作
        $request->controller(Loader::parseName($controller, 1))->action($actionName);

        // 监听module_init
        Hook::listen('module_init', $request);

        try {
            $instance = Loader::controller(
                $controller,
                $config['url_controller_layer'],
                $config['controller_suffix'],
                $config['empty_controller']
            );
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        // 获取当前操作名
        $action = $actionName . $config['action_suffix'];

        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
            // 严格获取当前操作方法名
            $reflect = new \ReflectionMethod($instance, $action);
            $methodName = $reflect->getName();
            $suffix = $config['action_suffix'];
            $actionName = $suffix ? substr($methodName, 0, -strlen($suffix)) : $methodName;
            $request->action($actionName);

        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$actionName];
        } else {
            // 操作不存在
            throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
        }

        Hook::listen('action_begin', $call);

        return static::invokeMethod($call, $vars);
    }

    /**
     * URL路由检测（根据PATH_INFO)
     * @access public
     * @param \think\Request $request 请求实例
     * @param array $config 配置信息
     * @return array
     * @throws \think\Exception
     */
    public static function routeCheck($request, array $config)
    {
        $path = $request->path();
        $depr = $config['pathinfo_depr'];
        $result = false;

        // 路由检测
        $check = !is_null(static::$routeCheck) ? static::$routeCheck : $config['url_route_on'];
        if ($check) {
            // 开启路由
            if (is_file(RUNTIME_PATH . 'route.php')) {
                // 读取路由缓存
                $rules = include RUNTIME_PATH . 'route.php';
                is_array($rules) && Route::rules($rules);
            } else {
                /** @var \app\common\service\plugin\PluginManager $plugin_manager */
                $plugin_manager = app('plugin.manager');
                $plugin_manager->importPluginConfig('route');
                $files = $config['route_config_file'];
                foreach ($files as $file) {
                    if (is_file(CONF_PATH . $file . CONF_EXT)) {
                        // 导入路由配置
                        $rules = include CONF_PATH . $file . CONF_EXT;
                        is_array($rules) && Route::import($rules);
                    }
                }
            }

            // 路由检测（根据路由定义返回不同的URL调度）
            $result = Route::check($request, $path, $depr, $config['url_domain_deploy']);
            $must = !is_null(static::$routeMust) ? static::$routeMust : $config['url_route_must'];

            if ($must && false === $result) {
                // 路由无效
                throw new RouteNotFoundException();
            }
        }

        // 路由无效 解析模块/控制器/操作/参数... 支持控制器自动搜索
        if (false === $result) {
            static::$routeFound = false;
            $result = Route::parseUrl($path, $depr, $config['controller_auto_search']);
        } else {
            //这里表明用到了使用路由了，这里就可以使用路由中间件管道了
            static::$routeFound = true;
        }

        return $result;
    }

    /**
     * 设置应用的路由检测机制
     * @access public
     * @param bool $route 是否需要检测路由
     * @param bool $must 是否强制检测路由
     * @return void
     */
    public static function route($route, $must = false)
    {
        static::$routeCheck = $route;
        static::$routeMust = $must;
    }

    public static function loadFile($file)
    {
        $file = strpos($file, '.') ? $file : APP_PATH . $file . EXT;
        if (is_file($file) && !isset(static::$file[$file])) {
            include $file;
            static::$file[$file] = true;
        }
    }

    /**
     * Configures an object with the initial property values.
     * @param object $object the object to be configured
     * @param array $properties the property initial values given in terms of name-value pairs.
     * @return object the object itself
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * Creates a new object using the given configuration.
     *
     * You may view this method as an enhanced version of the `new` operator.
     * The method supports creating an object based on a class name, a configuration array or
     * an anonymous function.
     *
     * Below are some usage examples:
     *
     * ```php
     * // create an object using a class name
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // create an object using a configuration array
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // create an object with two constructor parameters
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * Using [[\yii\di\Container|dependency injection container]], this method can also identify
     * dependent objects, instantiate them and inject them into the newly created object.
     *
     * @param string|array|callable $type the object type. This can be specified in one of the following forms:
     *
     * - a string: representing the class name of the object to be created
     * - a configuration array: the array must contain a `class` element which is treated as the object class,
     *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
     * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
     *   The callable should return a new instance of the object being created.
     *
     * @param array $params the constructor parameters
     * @return object the created object
     */
    public static function createObject($type, array $params = [])
    {
        return app('class.resolver')->createFromDefinition($type, $params);
    }
}