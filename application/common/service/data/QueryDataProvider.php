<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\data;


use app\common\service\base\InvalidCallException;
use EasyWeChat\Kernel\Support\Arr;
use think\db\Query;

class QueryDataProvider extends BaseDataProvider
{
    /**
     * @var string|callable 用来定义数据键的索引/方法
     */
    public $key;
    /**
     * @var \think\db\Query 待执行的数据查询
     */
    public $query;

    protected function prepareModels()
    {
        $instance_class = Query::class;
        if (!$this->query instanceof  $instance_class) {
            throw new InvalidCallException(sprintf('the property "query" must be some subclass of class "%s"', $instance_class));
        }
        $query = clone $this->query;

        if (($sort = $this->getSort()) !== false) {
            $orders = $sort->getOrders();
            foreach ($orders as $name => $dir) {
                if (is_integer($name)) {
                    $query->order($dir);
                } else if (is_string($name)) {
                    $query->order($name, $dir);
                }
            }
        }
        $pagination = $this->getPagination();
        if ($pagination === false) {
            $paginator = $this->query->paginate(Arr::get($this->paginationConfig, 'listRows', null), Arr::get($this->paginationConfig, 'simple', false), Arr::get($this->paginationConfig, 'options', []));
            $this->setPagination($paginator);
            $pagination = $this->getPagination();
        }

        return !$pagination ? [] : $pagination->items();
    }

    protected function prepareKeys($models)
    {
        if ($this->key !== null) {
            $keys = [];
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }
            return $keys;
        } else {
            $model = $this->query->getModel();
            if ($model) {
                return $model->getPk();
            }
        }

        return array_keys($models);
    }

    protected function prepareTotalCount()
    {
        return $this->query->count();
    }
}