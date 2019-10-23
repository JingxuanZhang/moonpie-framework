<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\validate;


use app\common\validate\BaseValidate;

class LocalUploadConfig extends BaseValidate
{
    protected $rule = [
        'active' => 'require|in:0,1',
        'setting' => 'require|validSetting',
        'base path' => 'require',
        'file' => 'require|validFileMode',
        'dir' => 'require|validFileMode',
        'permissions' => 'require|validPermission',
    ];
    protected $field = [
        'active' => '默认引擎',
        'setting' => '配置',
        'permissions' => '权限规则',
        'base path' => '上传根目录',
        'file' => '文件权限', 'dir' => '目录权限',
    ];
    protected $scene = [
        'default' => ['active', 'setting'],
        'setting' => ['base path', 'permissions'],
        'permissions' => ['file', 'dir'],
    ];
    protected $currentScene = 'default';
    protected function validSetting($setting, $rule, $data, $field, $title)
    {
        $label = "{$field}.validSetting";
        $validator = new self();
        $result = $validator->scene('setting')->check($setting);
        if(!$result) {
            $this->message($label, "{$title}配置错误：{$validator->getError()}");
            return false;
        }
        return true;
    }
    protected function validPermission($permissions, $rule, $data, $field, $title)
    {
        $label = "{$field}.validPermission";
        $validator = new self();
        $result = $validator->scene('permissions')->check($permissions);
        if(!$result) {
            $this->message($label, "{$title}配置错误：{$validator->getError()}");
            return false;
        }
        return true;
    }
    protected function validFileMode($mode, $rule, $data, $field, $title)
    {
        $label = "{$field}.validFileMode";
        $pass = $this->is($mode, "number");
        if(!$pass) {
            $this->message($label, "错误的{$title}格式。");
            return false;
        }
        return true;
    }
}