<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\base;


use app\common\service\helper\ArrayHelper;
use think\Collection as Base;
class Collection extends Base
{
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }
    public function only(array $keys)
    {
        $return = [];

        foreach ($keys as $key) {
            $value = $this->get($key);

            if (!is_null($value)) {
                $return[$key] = $value;
            }
        }

        return new static($return);
    }
    public function get($key, $default = null)
    {
        return ArrayHelper::get($this->items, $key, $default);
    }
    public function first(callable $callback = null, $default = null)
    {
        return ArrayHelper::first($this->items, $callback, $default);
    }
}