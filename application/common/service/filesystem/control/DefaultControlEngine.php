<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem\control;

use app\common\service\filesystem\exception\EngineControlNotFoundException;
use think\Request;

class DefaultControlEngine extends AbstractControlEngine
{
    public function handleEngineManage($code, Request $request)
    {
        $handler = $this->getMatchEngineHandler($code, $request);
        return $handler->handle($request);
    }

    public function getMatchEngineHandler($code, Request $request = null)
    {
        krsort($this->handlers, SORT_NUMERIC);
        foreach ($this->handlers as $priority => $handlers) {
            /**@var EngineHandlerInterface $handler */
            foreach ($handlers as $handler) {
                if ($handler->support($code)) {
                    return $handler;
                }
            }
        }
        throw new EngineControlNotFoundException(sprintf('指定的引擎无法处理，机读码:%s', $code));
    }
}
