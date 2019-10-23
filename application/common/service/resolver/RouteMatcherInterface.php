<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\resolver;


use think\Request;

interface RouteMatcherInterface
{
    /**
     * 返回匹配当前请求的分发信息
     * @param Request $request 需要匹配的请求
     * @return array
     */
    public function getMatchedDispatch(Request $request);

    /**
     * @param Request $request 需要匹配的请求
     * @return array|null 匹配当前请求的路由名标识,包换的有route info 和route name
     */
    public function getMatchedRouteInfo(Request $request);
}