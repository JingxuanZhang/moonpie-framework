<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\controller\upload;

use app\common\service\filesystem\traits\ControllerTrait;
use app\manager\controller\Base;

class Library extends Base
{
    use ControllerTrait;
    protected $groupScopeConfig = [
        'manage' => 'admin',
        'valid' => ['admin', ''],
    ];
}
