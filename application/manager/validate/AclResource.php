<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/13
 * Time: 9:53
 */

namespace app\manager\validate;


use think\Validate;

class AclResource extends Validate
{
    protected $rule = [
        'title' => 'require|length:2, 100',
        'description' => 'require|length: 2,200',
        'code' => 'require|alphaDash|unique:acl_resource,code',
        'pid' => 'validParent',
        'id' => 'validId',
        'resource_id' => 'require|validResourceId',
    ];
    protected $field = [
        'title' => '权限资源标题',
        'description' => '权限资源描述',
        'code' => '权限资源机读码',
        'pid' => '继承资源',
        'resource_id' => '资源实例ID',
        'id' => '资源ID'
    ];
    protected $scene = [
        'edit' => [
            'title', 'description', 'pid'
        ]
    ];

    protected function validResourceId($id, $rule, $data, $field, $title)
    {
        $label = "{$field}.validResourceId";
        $registry = app('authorize.acl_res_registry');
        if (!$registry->has($id)) {
            $this->message($label, "{$title}不是支持的资源类型");
            return false;
        }
        return true;
    }

    protected function validParent($parent, $rule, $data, $field, $title)
    {
        $label = "{$field}.validParent";
        //必须是存在的角色信息
        if (\app\manager\model\AclResource::where('id', $parent)->count() == 0) {
            $this->message($label, "{$title}中存在错误的权限资源信息");
            return false;
        }

        //继承的角色不能又依赖自己
        if (isset($data['id'])) {
            //继承的角色不能是他自己
            if ($data['id'] == $parent) {
                $this->message($label, "{$title}中不能包含自己");
                return false;
            }
            $count = \app\manager\model\AclResource::where('id', $parent)
                ->where('pid', $data['id'])->count();
            if ($count > 0) {
                $this->message($label, "{$title}中存在依赖资源本身的元素");
                return false;
            }
        }
        return true;
    }

    protected function validId($id, $rule, $data, $field, $title)
    {
        $label = "{$field}.validId";
        $has_one = \app\manager\model\AclResource::where('id', $id)->count() > 0;
        if (!$has_one) {
            $this->message($label, "{$title}信息不存在");
            return false;
        }
        return true;
    }
}