<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\behavior;


use app\common\service\Console;
use think\Queue;

class ConsoleBehavior
{
    public function prepareThinkPHPQueueCommand($params)
    {
        /** @var Console $console */
        $console = $params['console'];
        if(class_exists(Queue::class)) {
            $console->addCommands([
                new \think\queue\command\Work(),
                new \think\queue\command\Restart(),
                new \think\queue\command\Listen(),
                new \think\queue\command\Subscribe(),
            ]);
        }
    }
}