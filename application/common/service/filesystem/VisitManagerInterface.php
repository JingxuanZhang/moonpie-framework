<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem;

/**
 * 为引擎提供外部可以访问的域名的接口
 * Interface VisitManagerInterface
 * @package app\common\service\filesystem
 */
interface VisitManagerInterface
{
    public function getDomain($code, array $configs);
}
