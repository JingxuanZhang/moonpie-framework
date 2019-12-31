<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\routing;


use app\common\service\pipeline\BasePipeLine;
use app\common\service\ServiceContainer;
use EasyWeChat\Kernel\Support\Arr;
use Psr\Container\ContainerInterface;
use think\Config;
use think\Request;
use think\Route;

class Router
{
    protected $container;
    protected $globalMiddleware = [];
    protected $middleware = [];
    protected $middlewareGroups = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatchToRequest(Request $request, \Closure $then)
    {
        return $this->runRouteWithinStack($request)
            ->then($then);
    }

    /**
     * Run the given route within a Stack "onion" instance.
     * @param Request $request
     * @return BasePipeLine
     */
    protected function runRouteWithinStack(Request $request)
    {
        $middleware = $this->gatherRouteMiddleware($request);
        return (new BasePipeLine($this->container))
            ->send($request)
            ->through($middleware);
    }

    protected function gatherRouteMiddleware(Request $request)
    {
        $middleware_declare = (array)Arr::get($request->routeInfo(), 'option.middleware', []);
        return Arr::flatten(array_map(function ($name) {
            return (array)MiddlewareNameResolver::resolve($name, $this->middleware, $this->middlewareGroups);
        }, $middleware_declare));
    }

    /**
     * 判断指定的链接和某一请求对象是否一致
     * @param $link
     * @param Request $request
     * @return bool
     */
    public function isLinkMatchRequest($link, Request $request)
    {
        $depr = Config::get('pathinfo_depr');
        $route_check = Route::check($request, $this->convertMatchLink($link, $depr), $depr);
        if ($route_check !== false) {
            $dispatch = $request->dispatch();
            return $dispatch === $route_check;
        }
        return false !== $route_check;
    }

    protected function convertMatchLink($link, $depr = '/')
    {
        $suffix = Config::get('url_html_suffix');
        if (false === $suffix) {
            // 禁止伪静态访问
            $return = $link;
        } elseif ($suffix) {
            // 去除正常的URL后缀
            $return = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $link);
        } else {
            // 允许任何后缀访问
            $return = preg_replace('/\.' . pathinfo($link, PATHINFO_EXTENSION) . '$/i', '', $link);
        }
        return ltrim($return, $depr);
    }

    /**
     * Get all of the defined middleware short-hand names.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Register a short-hand name for a middleware.
     *
     * @param string $name
     * @param string $class
     * @return $this
     */
    public function aliasMiddleware($name, $class)
    {
        $this->middleware[$name] = $class;

        return $this;
    }

    /**
     * Check if a middlewareGroup with the given name exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasMiddlewareGroup($name)
    {
        return array_key_exists($name, $this->middlewareGroups);
    }

    /**
     * Get all of the defined middleware groups.
     *
     * @return array
     */
    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    /**
     * Register a group of middleware.
     *
     * @param string $name
     * @param array $middleware
     * @return $this
     */
    public function middlewareGroup($name, array $middleware)
    {
        $this->middlewareGroups[$name] = $middleware;

        return $this;
    }

    public function globalMiddleware(array $middleware)
    {
        $this->globalMiddleware = $middleware;
        return $this;
    }

    public function prependGlobalMiddleware($middleware)
    {
        if (!in_array($middleware, $this->globalMiddleware)) {
            array_unshift($this->globalMiddleware, $middleware);
        }
        return $this;
    }
    public function pushGlobalMiddleware($middleware)
    {

        if (!in_array($middleware, $this->globalMiddleware)) {
            $this->globalMiddleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Add a middleware to the beginning of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param string $group
     * @param string $middleware
     * @return $this
     */
    public function prependMiddlewareToGroup($group, $middleware)
    {
        if (isset($this->middlewareGroups[$group]) && !in_array($middleware, $this->middlewareGroups[$group])) {
            array_unshift($this->middlewareGroups[$group], $middleware);
        }

        return $this;
    }

    /**
     * Add a middleware to the end of a middleware group.
     *
     * If the middleware is already in the group, it will not be added again.
     *
     * @param string $group
     * @param string $middleware
     * @return $this
     */
    public function pushMiddlewareToGroup($group, $middleware)
    {
        if (!array_key_exists($group, $this->middlewareGroups)) {
            $this->middlewareGroups[$group] = [];
        }

        if (!in_array($middleware, $this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group][] = $middleware;
        }

        return $this;
    }
    public function getGlobalMiddleware()
    {
        return $this->globalMiddleware;
    }
}