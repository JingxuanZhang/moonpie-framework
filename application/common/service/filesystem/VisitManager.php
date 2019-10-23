<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem;

use EasyWeChat\Kernel\Support\Arr;

class VisitManager implements VisitManagerInterface
{
    public function getDomain($code, array $configs)
    {
        switch ($code) {
            case 'local':
                return base_url() . trim(Arr::get($configs, 'base path', Arr::get($configs, 'domain')), '\\/') . '/';
            default:
                return trim(Arr::get($configs, 'domain', ''), '\\/') . '/';
        }
    }
}
