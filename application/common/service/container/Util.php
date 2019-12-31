<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\container;

use Closure;
class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * From Arr::wrap() in Illuminate\Support.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function arrayWrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }
    /**
     * Return the default value of the given value.
     *
     * From global value() helper in Illuminate\Support.
     *
     * @param  mixed  $value
     * @return mixed
     */
    public static function unwrapIfClosure($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}