<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\resolver;


interface ClassResolverInterface
{
    /**
     * @param $flag mixed 类信息
     * @param array $definition 构造器信息
     * @param bool $returnId 是否返回类标识
     * @return mixed
     */
    public function createFromDefinition($flag, $definition = [], $returnId = false);
}