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

interface EngineHandlerInterface
{
    /**
     * 是否支持某个引擎代码
     * @param string 引擎机读码
     * @return bool
     */
    public function support($code);
    /**
     * 处理具体的引擎
     * @param Request $request
     * @return \think\Response
     */
    public function handle(Request $request);

    /**
     * @param Request $request
     * @return string
     */
    public function renderViewForExternal(Request $request);

    /**
     * 处理外部请求中的数据请求,完成验证相关
     * @param Request $request
     * @return array
     */
    public function handleExternalRequest(Request $request);
}
