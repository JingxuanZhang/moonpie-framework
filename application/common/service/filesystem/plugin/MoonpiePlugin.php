<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem\plugin;

use League\Flysystem\Plugin\AbstractPlugin;

class MoonpiePlugin extends AbstractPlugin
{
    public function getMethod()
    {
        return 'moonpieMethod';
    }
    public function handle($method, array $args = [])
    {
        $method_name = 'handle' . ucfirst($method);
        return call_user_func_array([$this, $method_name], $args);
    }
}
