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
     * 为外部提供接口获取配置相关的视图信息
     * @param Request $request
     * @param array $viewConfig 视图相关配置
     * @return string
     */
    public function renderViewForExternal(Request $request, array $viewConfig);

    /**
     * 处理外部请求中的数据请求,完成验证相关
     * @param Request $request
     * @return array 返回请求中设置的请求信息,如果系统出现错误抛出一个异常
     */
    public function handleExternalRequest(Request $request);
}
