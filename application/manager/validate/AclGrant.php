<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 * (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/13
 * Time: 9:53
 */

namespace app\manager\validate;


use think\Validate;

class AclGrant extends Validate
{
    protected $rule = [
        'title' => 'require|length:2,40',
        'role_id' => 'validRoleId',
        'resource_id' => 'validResourceId',
        'granted' => 'require|boolean',
        'use_code' => 'require|boolean',
    ];
    protected $field = [
        'title' => '规则标题',
        'role_id' => '适用角色',
        'resource_id' => '使用资源',
        'granted' => '是否授权',
        'use_code' => '是否使用机读码',
    ];
    protected $scene = [
        'edit' => [
            'title', 'granted', 'use_code',
        ]
    ];

    protected function validResourceId($id, $rule, $data, $field, $title)
    {
        $label = "{$field}.validResourceId";
        $exists = \app\manager\model\AclResource::where('id', $id)->count() > 0;
        if (!$exists) {
            $this->message($label, "{$title}不是有效的资源信息");
            return false;
        }
        return true;
    }

    protected function validRoleId($id, $rule, $data, $field, $title)
    {
        $label = "{$field}.validRoleId";
        $exists = \app\manager\model\AclRole::where('id', $id)->count() > 0;
        if (!$exists) {
            $this->message($label, "{$title}不是有效的角色信息");
            return false;
        }
        return true;
    }
}