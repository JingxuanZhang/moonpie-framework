<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem\control;

use think\Request;

/**
 * 处理引擎配置信息的接口
 * 此接口是一个容器，用来存储指定引擎的配置控制器信息：通过addEngineHandler方法。
 * 如果没有处理指定引擎的控制器，应当抛出异常：详细请看handleEngineManage方法。
 */
interface ControlEngineInterface
{
    /**
     * 处理引擎的配置信息，包括是否是默认引擎，各个引擎的配置
     * @throws \app\common\service\filesystem\exception\EngineControlNotFoundException
     * @param $code
     * @param Request $request
     * @return mixed
     * @return \think\Response
     */
    public function handleEngineManage($code, Request $request);
    /**
     * 添加引擎处理器
     * @param EngineHandlerInterface $handler
     * @param int $priority
     * @return $this
     */
    public function addEngineHandler(EngineHandlerInterface $handler, $priority = 0);
}
