<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\controller;

use app\common\service\controller\PlainBackendController;
use think\Env;
use think\Request;

/**
 * 商户后台控制器基类
 * Class BaseController
 * @package app\store\controller
 */
abstract class Base extends PlainBackendController
{
    protected $menuName = 'admin_panel';
    protected $allowAllAction = [
        'backend.login',
    ];

    protected function getAuthSessionKey()
    {
        return config('backend.session_name');
    }

    protected function getUrlPrefix()
    {
        return '/' . Env::get('BACKEND_NAME', 'admin');
    }

    protected function handleNeedLogin(Request $request)
    {
        $this->redirect('backend.login');
    }
}
