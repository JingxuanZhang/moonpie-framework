<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/13
 * Time: 9:53
 */

namespace app\manager\validate;


use think\Db;
use think\Validate;

class AclRole extends Validate
{
    protected $rule = [
        'title' => 'require|length:2, 100',
        'description' => 'require|length: 2,200',
        'code' => 'require|alphaDash|unique:acl_role,code',
        'parents' => 'validParents',
        'role_id' => 'validRoleId',
    ];
    protected $field = [
        'title' => '角色标题',
        'description' => '角色描述',
        'code' => '角色机读码',
        'parents' => '继承角色',
        'role_id' => '角色ID',
    ];
    protected $scene = [
        'edit' => [
            'title', 'description', 'parents', 'role_id',
        ]
    ];

    protected function validParents($parents, $rule, $data, $field, $title)
    {
        $label = "{$field}.validParents";
        //必须是存在的角色信息
        if (\app\manager\model\AclRole::whereIn('id', $parents)->count() !=  count($parents)) {
            $this->message($label, "{$title}中存在不存在的角色信息");
            return false;
        }

        //继承的角色不能又依赖自己
        if (isset($data['role_id'])) {
            //继承的角色不能是他自己
            if (in_array($data['role_id'], $parents)) {
                $this->message($label, "{$title}中不能包含自己");
                return false;
            }
            $count = Db::name('acl_role_parent')->whereIn('role_id', $parents)
                ->where('pid', $data['role_id'])->count();
            if ($count > 0) {
                $this->message($label, "{$title}中存在依赖角色本身的元素");
                return false;
            }
        }
        return true;
    }

    protected function validRoleId($id, $rule, $data, $field, $title)
    {
        $label = "{$field}.validRoleId";
        $has_one = \app\manager\model\AclRole::where('id', $id)->count() > 0;
        if (!$has_one) {
            $this->message($label, "{$title}信息不存在");
            return false;
        }
        return true;
    }
}