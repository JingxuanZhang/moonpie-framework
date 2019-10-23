<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\controller;

use app\manager\model\Admin;

class Login extends Base
{
    public function index()
    {
        if ($this->request->isAjax()) {
            $admin = new Admin();
            if ($admin->login($this->postData('admin'))) {
                return $this->renderSuccess('登录成功', url('backend.home'));
            }
            return $this->renderError($admin->getError() ?: '登录失败');
        }
        return $this->fetch();
    }

    public function logout()
    {
        if(isset($this->store['admin_user'])) $this->store['admin_user']->logout();
        $this->redirect('backend.login');
    }

}
