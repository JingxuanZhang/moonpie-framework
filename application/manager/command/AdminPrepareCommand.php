<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/6/3
 * Time: 8:48
 */

namespace app\manager\command;


use app\common\model\AclRole;
use app\manager\model\Admin;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Loader;

class AdminPrepareCommand extends Command
{
    protected function configure()
    {
        return $this->setName('app:backend-init')
            ;
    }
    protected function execute(Input $input, Output $output)
    {
        //首先判断有没有用户
        $has_admin = Admin::count() > 0;
        if($has_admin) {
            $output->warning('have super admin already, please use it');
            return;
        }
        //新增限制，如果没有超管权限也不能添加
        $admin_role = AclRole::where('code', 'super_admin')->find();
        if(!$admin_role) {
            $output->warning('has no super admin yet, please run command `php think seed:run `');
        }
        $username = $output->ask($input, 'What is the super admin name', 'admin');
        $password = $output->askHidden($input, 'what is the super admin password?');
        $password_confirm = $output->askHidden($input, 'please repeat your password again.');
        /** @var \app\manager\validate\Admin $validator */
        $validator = Loader::validate('manager/Admin', 'validate', false, 'manager');
        $data = compact('username', 'password', 'password_confirm');
        $result = $validator->check($data);
        if(!$result) {
            $output->error('data error: ' . $validator->getError());
            return;
        }
        $user = new Admin();
        $data['role_id'] = $admin_role['id'];
        $data['create_at'] = $data['update_at'] = time();
        $result = false !== $user->add($data);
        if(!$result) {
            $output->error('save user failed, please try again later');
            return;
        }
        $output->info('create user success');
    }
}