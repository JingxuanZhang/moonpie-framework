<?php

/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 * (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace app\manager\controller;

use app\manager\model\Admin as AdminModel;
use think\Request;

class Admin extends Base
{

    public function renew()
    {
        $account = $this->getCurrentAccount();
        if ($this->request->isPost()) {
            $data = $this->request->post('user/a');
            $error = $this->validate($data, [
                'password|密码' => 'length:6,20|alphaDash|confirm'
            ]);
            if (true !== $error) {
                $this->error($error);
            }
            $result = $account->changePassword($data['password']);
            if (!$result) {
                $this->error($account->getError());
            }
            $this->success('修改用户密码成功', url('backend.home'));
        }
        $vars = [
            'page_title' => '管理员设置',
            'account' => $account,
        ];
        $this->assign($vars);
        return $this->fetch();
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $model = new AdminModel();
        $list = $model->getList();
        $this->assign([
            'list' => $list,
            'page_title' => '用户列表',
        ]);
        return $this->fetch();
    }


    public function add()
    {
        if ($this->request->isAjax()) {
            $model = new AdminModel();
            if ($model->add($this->postData('user'))) {
                return $this->renderSuccess('添加成功', url('admin/manager'));
            }
            return $this->renderError($model->getError() ?: '添加失败');
        }
        return $this->fetch();
    }


    public function delete(Request $request)
    {
        $adminId = $request->param('admin_id');
        $admin = new AdminModel();
        $result = $admin->where('id', $adminId)->delete();
        if ($result) {
            return $this->renderSuccess('删除成功');
        }
        return $this->renderError($admin->getError() ?: '删除失败');
    }
}
