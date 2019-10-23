<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\service\provider;


use app\manager\service\filesystem\control\LocalHandler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ManagerProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        /** @var \app\common\service\ServiceContainer $pimple */
        /** @var \app\common\service\ServiceContainer $c*/
        //添加相关服务
        $pimple['manager.filesystem_local_control_handler'] = function($c){
            return new LocalHandler();
        };
        $pimple->tag('manager.filesystem_local_control_handler', 'storage.control_handler', ['priority' => 10]);
    }
}