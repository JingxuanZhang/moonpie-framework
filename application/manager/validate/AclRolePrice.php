<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/13
 * Time: 9:53
 */

namespace app\manager\validate;


use app\manager\model\AclRole;
use app\manager\model\AclRolePrice as Model;
use think\Validate;

class AclRolePrice extends Validate
{
    protected $rule = [
        'from_role' => 'require|validFromRole',
        'to_role' => 'require|validToRole',
        'price' => 'require|float|min:0|max:999999.99',
        'valid_seconds' => 'require|integer|egt:0',
        'deadline' => 'validDeadline',
    ];
    protected $field = [
        'from_role' => '来源角色',
        'to_role' => '目标角色',
        'price' => '转换价格',
        'valid_seconds' => '有效时长',
        'deadline' => '截止日期',
    ];
    protected $scene = [
        'edit' => [
            'price', 'deadline', 'valid_seconds'
        ]
    ];

    protected function validToRole($roleId, $rule, $data, $field, $title)
    {
        $label = "{$field}.validToRole";
        //必须是存在的角色信息
        if (AclRole::where('id', $roleId)->count() != 1) {
            $this->message($label, "{$title}中存在不存在的角色信息");
            return false;
        }
        //确保记录唯一
        $exists = Model::where('from_role', $data['from_role'])
            ->where('to_role', $roleId)
            ->count() > 0;
        if($exists) {
            $this->message($label, "该转换规则已存在，不能使用此规则");
            return false;
        }

        return true;
    }

    protected function validFromRole($roleId, $rule, $data, $field, $title)
    {
        $label = "{$field}.validFromRole";
        //必须是存在的角色信息
        if (AclRole::where('id', $roleId)->count() != 1) {
            $this->message($label, "{$title}中存在不存在的角色信息");
            return false;
        }

        //来源不得和目标一样
        if ($roleId == $data['to_role']) {
            $this->message($label, "{$title}不得和目标角色一样");
            return false;
        }

        return true;
    }

    protected function validDeadline($deadline, $rule, $data, $field, $title)
    {
        $label = "{$field}.validDeadline";
        try {
            $date = new \DateTimeImmutable($deadline);
        }catch (\Exception $e) {
            $this->message($label, "{$title}不是合法的日期数据");
            return false;
        }
        return true;
    }
}