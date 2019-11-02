<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\menu;


use app\common\service\security\AccountInterface;
use EasyWeChat\Kernel\Support\Arr;

class MenuElement
{
    protected $element = [];
    protected $urlObject;
    protected $activeTrail;
    protected $uniqueKey;
    protected $subTree;

    public function __construct($element, $uniqueKey)
    {
        $this->element = $element;
        $this->uniqueKey = $uniqueKey;
    }

    public function hasChildren()
    {
        return Arr::has($this->element, 'sub_data') && !empty($this->element['sub_data']);
    }

    public function getSubTree()
    {
        if (is_null($this->subTree)) {
            if (!$this->hasChildren() || !$this->isEnable()) return $this->subTree = [];
            $sub = $this->element['sub_data'];
            uasort($sub, function ($a, $b) {
                $a_weight = $a->getWeight();
                $b_weight = $b->getWeight();
                return $a_weight > $b_weight ? 1 : $a_weight < $b_weight ? -1 : 0;
            });
            return $this->subTree = $sub;
        }
        return $this->subTree;
    }

    public function getWeight()
    {
        return Arr::get($this->element, 'weight', 0);
    }

    public function getTitle()
    {
        $callback = $this->getAttribute('title callback');
        if (is_callable($callback)) return call_user_func($callback);
        return $this->getAttribute('title');
    }

    public function getIcon()
    {
        $callback = $this->getAttribute('icon callback');
        if (is_callable($callback)) return call_user_func($callback);
        return $this->getAttribute('icon');
    }

    public function getDescription()
    {
        return $this->getAttribute('description', '');
    }

    public function getUrlObject()
    {
        if (is_null($this->urlObject)) {
            //优先判断外部链接
            $url = $this->getAttribute('url');
            if (!empty($url)) {
                return $this->urlObject = MenuLink::createExternalLink($url);
            }
            $route_name = $this->getAttribute('route name', '');
            if (empty($route_name)) {
                return $this->urlObject = new MenuLink('', true);
            }
            //默认返回内部链接
            return $this->urlObject = MenuLink::createInternalLink($this->getAttribute('route name'), $this->getAttribute('route parameters', []));
        }
        return $this->urlObject;
    }

    public function getAttribute($prop, $default = null)
    {
        return Arr::get($this->element, $prop, $default);
    }

    public function getOption($option, $default = null)
    {
        return Arr::get(Arr::get($this->element, 'attributes', []), $option, $default);
    }

    public function isEnable()
    {
        //暂时使用配置
        //这里判断是否需要权限
        //首先使用的是认证回调，系统会处理配置中的参数
        if (isset($this->element['auth callback'])) {
            $callback = static::getServiceFromIdentifier($this->element['auth callback']);//尽可能实例化这个认证回调
            if (is_callable($callback)) {
                /** @var AccountInterface $account */
                $account = app('current_account');
                //这里会调用此回调，参数是当前用户和当前菜单
                $has_permission = call_user_func($callback, $account, $this);
                if ($has_permission) return true;
            }
            return false;
        } else if (isset($this->element['permission'])) {
            $permission = $this->element['permission'];
            $resource = static::parseAclResource($this->element);
            /** @var AccountInterface $account */
            $app = app(true);
            if (isset($app['current_account'])) {
                $account = $app['current_account'];
                $has_permission = $account->hasPermission($permission, $resource);
                if ($has_permission) return true;
            }
            return false;
        }
        //需要结合权限来判断当前用户是否可以访问
        return true;
    }
    private static function getServiceFromIdentifier($callback)
    {
        if (is_callable($callback)) return $callback;
        /** @var \app\common\service\resolver\ClassResolverInterface $service */
        $service = app('class.resolver');
        $class_object = $service->createFromDefinition($callback);
        return [$class_object, 'checkAccess'];
    }
    private static function parseAclResource($config)
    {
        $resource_config = Arr::get($config, 'acl resource', null);
        if (empty($resource_config)) return null;
        if (is_string($resource_config)) return $resource_config;
        if (is_array($resource_config)) {
            if (is_callable($resource_config)) return call_user_func($resource_config, $config);
            if (Arr::has($resource_config, ['type', 'field'])) {
                $type = Arr::get($resource_config, 'type', 'request');
                switch ($type) {
                    case 'service':
                        return app(true)->offsetGet($resource_config['field']);
                    case 'common':
                        return $resource_config['field'];
                    case 'request':
                    default:
                        return request()->{$resource_config['field']};
                }
            }
        }
        return null;
    }

    public function isActiveTrail()
    {
        return !empty($this->activeTrail);
    }

    public function useSvgIcon()
    {
        return false;
    }

    public function getElement()
    {
        return $this->element;
    }

    public function calcActiveTrail()
    {
        $activeTrail = $this->getUrlObject()->isActiveTrail();
        if ($activeTrail) return $this->activeTrail = $this->uniqueKey;
        if ($this->hasChildren()) {
            $sub = $this->getSubTree();
            foreach ($sub as $name => $sub_one) {
                $sub_trail = $sub_one->calcActiveTrail();
                if (!empty($sub_trail)) {
                    return $this->activeTrail = $sub_trail;
                }
            }
        }
        return $this->activeTrail = null;
    }

    public function isContainer()
    {
        return $this->hasChildren();
    }

    public function getProxyClickableElement()
    {
        if ($this->isContainer()) {
            $index = $this->getAttribute('default link');
            if (!empty($index)) {
                if(isset($this->element['sub_data'][$index]) && !is_null($this->element['sub_data'][$index])) {
                    return $this->element['sub_data'][$index];
                }
            }
            return false;
        }
        return $this;
    }
}