<?php
/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

namespace app\common\service\filesystem\control;


abstract class AbstractControlEngine implements ControlEngineInterface
{
    protected $handlers = [];
    public function addEngineHandler(EngineHandlerInterface $handler, $priority = 0)
    {
        if (!isset($this->handlers[$priority])) $this->handlers[$priority] = [];
        array_unshift($this->handlers[$priority], $handler);
        return $this;
    }
}
