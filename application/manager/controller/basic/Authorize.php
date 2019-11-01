<?php

/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 * (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace app\manager\controller\basic;


use app\manager\controller\Base;
use app\common\model\AclResource;
use app\common\model\AclRole;
use app\common\model\AclRolePrice;
use app\common\model\AclUserAclGrant;
use think\Exception;
use think\Loader;

class Authorize extends Base
{
    public function index()
    {
        $this->assign('page_title', '角色管理');
        $this->assign('add_url', url('backend.authorize_add_role'));
        $this->assign('list', AclRole::where('id', '>', 0)->paginate(10));
        $this->assign('del_url', url('backend.authorize_drop_role'));
        $this->assign('edit_uri', 'backend.authorize_edit_role');
        return $this->fetch();
    }

    public function edit_role($id)
    {
        $role = AclRole::get($id);
        if (!$role) abort(404, '没有找到指定的角色信息');
        if ($this->request->isPost()) {
            $data = $this->request->post('role/a');
            /** @var \app\manager\validate\AclRole $validator */
            $validator = Loader::validate('manager/AclRole', 'validate', false, 'manager');
            $result = $validator->scene('edit')->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $role->startTrans();
            try {
                if (isset($data['parents'])) {
                    $parents = $data['parents'];
                    unset($data['parents']);
                }
                $result = false !== $role->save($data);
                if (!$result) {
                    $role->rollback();
                    $this->error($role->getError());
                }
                if ($result) {
                    //现在开始处理关联部分
                    $now = time();
                    $sync = empty($parents) ? [] : array_fill_keys($parents, ['create_at' => $now, 'update_at' => $now]);
                    $result = false !== $role->parents()->sync($sync, true);
                    if (!$result) {
                        $role->rollback();
                        $this->error('保存数据出现错误，请稍后重试');
                    }
                }
                $role->commit();
                $this->success('编辑成功', 'backend.authorize_index');
            } catch (Exception $e) {
                $role->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('parents', $role->parents);
        $this->assign('page_title', '编辑角色');
        $this->assign('role', $role);
        $this->assign('need_code', !isset($role['id']));
        return $this->fetch('basic/authorize/add_role');
    }

    public function add_role()
    {
        $role = new AclRole(['title' => '', 'description' => '', 'code' => '']);
        if ($this->request->isPost()) {
            $data = $this->request->post('role/a');
            /** @var \app\manager\validate\AclRole $validator */
            $validator = Loader::validate('manager/AclRole', 'validate', false, 'manager');
            $result = $validator->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $role->startTrans();
            try {
                if (isset($data['parents'])) {
                    $parents = $data['parents'];
                    unset($data['parents']);
                }
                $result = false !== $role->save($data);
                if (!$result) {
                    $role->rollback();
                    $this->error($role->getError());
                }

                if ($result && !empty($parents)) {
                    $now = time();
                    $result = false !== $role->parents()->saveAll($parents, ['create_at' => $now, 'update_at' => $now], true);
                    if (!$result) {
                        $role->rollback();
                        $this->error('保存数据出现错误，请稍后重试');
                    }
                }
                $role->commit();
                $this->success('创建成功', 'backend.authorize_index');
            } catch (Exception $e) {
                $role->rollback();
                $this->error('数据存储出现异常，请稍后重试' . $e->getMessage());
            }
        }
        $this->assign('parents', null);
        $this->assign('page_title', '添加角色');
        $this->assign('role', $role);
        $this->assign('need_code', !isset($role['id']));
        return $this->fetch('basic/authorize/add_role');
    }

    public function query_role()
    {
        if ($this->request->isPost()) {
            $page = $this->request->post('page', 1);
            $keyword = $this->request->post('keyword');
            if (!empty($keyword)) {
                $offset = max(0, $page - 1);
                $query = AclRole::where('code|title', 'like', "{$keyword}%")
                    ->limit($offset, 10)
                    ->field(['id', 'title', 'code', 'description']);
                if ($this->request->has('except_id', 'post')) {
                    $query->where('id', '<>', $this->request->post('except_id'));
                }
                $results = $query->select();
                return json(['data' => $results]);
            }
        }
    }


    //资源管理部分
    public function res()
    {
        $this->assign('page_title', '权限资源管理');
        $this->assign('add_url', url('backend.authorize_add_res'));
        $this->assign('list', AclResource::where('id', '>', 0)->paginate(10));
        $this->assign('del_url', url('backend.authorize_drop_res'));
        $this->assign('edit_uri', 'backend.authorize_edit_res');
        return $this->fetch();
    }

    public function edit_res($id)
    {
        $resource = AclResource::get($id);
        if (!$resource) abort(404, '没有找到指定的权限资源信息');

        if ($this->request->isPost()) {
            $data = $this->request->post('resource/a');
            /** @var \app\manager\validate\AclResource $validator */
            $validator = Loader::validate('manager/AclResource', 'validate', false, 'manager');
            $result = $validator->scene('edit')->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $resource->startTrans();
            try {
                $result = false !== $resource->save($data);
                if (!$result) {
                    $resource->rollback();
                    $this->error($resource->getError());
                }

                $resource->commit();
                $this->success('修改成功', 'backend.authorize_res');
            } catch (Exception $e) {
                $resource->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '添加权限资源');
        $this->assign('resource', $resource);
        $this->assign('need_code', !isset($resource['id']));
        $this->assign('parent', $resource->inherit);
        $registry = app('authorize.acl_res_registry');
        $this->assign('registry', $registry);
        $this->assign('default_class', $resource->getData('resource_id'));
        return $this->fetch('basic/authorize/add_res');
    }

    public function add_res()
    {
        $resource = new AclResource(['title' => '', 'description' => '', 'code' => '', 'resource_id' => '', 'pid' => null]);
        if ($this->request->isPost()) {
            $data = $this->request->post('resource/a');
            /** @var \app\manager\validate\AclResource $validator */
            $validator = Loader::validate('manager/AclResource', 'validate', false, 'manager');
            $result = $validator->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $resource->startTrans();
            try {
                $result = false !== $resource->save($data);
                if (!$result) {
                    $resource->rollback();
                    $this->error($resource->getError());
                }

                $resource->commit();
                $this->success('创建成功', 'backend.authorize_res');
            } catch (Exception $e) {
                $resource->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '添加权限资源');
        $this->assign('resource', $resource);
        $this->assign('need_code', !isset($resource['id']));
        $this->assign('parent', null);
        $registry = app('authorize.acl_res_registry');
        $this->assign('registry', $registry);
        $this->assign('default_class', '');
        return $this->fetch('basic/authorize/add_res');
    }

    public function query_res()
    {
        if ($this->request->isPost()) {
            $page = $this->request->post('page', 1);
            $keyword = $this->request->post('keyword');
            if (!empty($keyword)) {
                $offset = max(0, $page - 1);
                $query = AclResource::whereLike('code', "{$keyword}%")
                    ->whereOr(function ($q) use ($keyword) {
                        $q->whereLike('title', "{$keyword}%");
                    })->limit($offset, 10)
                    ->field(['id', 'title', 'code', 'description']);
                if ($this->request->has('except_id', 'post')) {
                    $query->where('id', '<>', $this->request->post('except_id'));
                }
                $results = $query->select();
                return json(['data' => $results]);
            }
        }
    }

    //授权规则部分
    public function rules()
    {
        $this->assign('page_title', '权限授权管理');
        $this->assign('add_url', url('basic.authorize/add_rule'));
        $this->assign('list', AclUserAclGrant::where('id', '>', 0)->paginate(10));
        $this->assign('del_url', url('backend.authorize_drop_rule'));
        $this->assign('edit_uri', 'backend.authorize_edit_rule');
        return $this->fetch();
    }

    public function edit_rule($id)
    {
        $rule = AclUserAclGrant::get($id);
        if (!$rule) abort(404, '没有找到指定的规则信息');

        if ($this->request->isPost()) {
            $data = $this->request->post('rule/a');
            /** @var \app\manager\validate\AclGrant $validator */
            $validator = Loader::validate('manager/AclGrant', 'validate', false, 'manager');
            $result = $validator->scene('edit')->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $rule->startTrans();
            try {
                $result = false !== $rule->save($data);
                if (!$result) {
                    $rule->rollback();
                    $this->error($rule->getError());
                }

                $rule->commit();
                $this->success('编辑成功', 'backend.authorize_rule');
            } catch (Exception $e) {
                $rule->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '编辑权限规则');
        $this->assign('rule', $rule);
        $this->assign('resource', $rule->resource);
        $this->assign('role', $rule->role);
        $this->assign('is_edit', true);
        $this->assign('query_role_url', url('backend.authorize_query_role'));
        $this->assign('query_res_url', url('backend.authorize_query_res'));
        $this->assign('query_assertion_url', url('backend.authorize_query_assertion'));
        return $this->fetch('basic/authorize/add_rule');
    }

    public function add_rule()
    {
        $rule = new AclUserAclGrant([
            'title' => '', 'role_id' => null, 'resource_id' => null, 'use_code' => true,
            'assertion' => '', 'privileges' => [],
            'granted' => true
        ]);
        if ($this->request->isPost()) {
            $data = $this->request->post('rule/a');
            /** @var \app\manager\validate\AclGrant $validator */
            $validator = Loader::validate('manager/AclGrant', 'validate', false, 'manager');
            $result = $validator->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $rule->startTrans();
            try {
                $result = false !== $rule->save($data);
                if (!$result) {
                    $rule->rollback();
                    $this->error($rule->getError());
                }

                $rule->commit();
                $this->success('创建成功', 'backend.authorize_rule');
            } catch (Exception $e) {
                $rule->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '添加权限规则');
        $this->assign('rule', $rule);
        $this->assign('resource', null);
        $this->assign('role', null);
        $this->assign('is_edit', false);
        $this->assign('query_role_url', url('backend.authorize_query_role'));
        $this->assign('query_res_url', url('backend.authorize_query_res'));
        $this->assign('query_assertion_url', url('backend.authorize_query_assertion'));
        return $this->fetch('basic/authorize/add_rule');
    }

    public function query_assertion()
    {
        if ($this->request->isPost()) {
            $keyword = $this->request->post('keyword');
            if (!empty($keyword)) {
                /** @var \app\common\service\security\AssertionRegistry $registry */
                $registry = app('authorize.acl_assert_registry');
                $classes = $registry->getAssertClasses();
                $items = array_map(function ($item, $id) {
                    return ['id' => $id, 'text' => $item['title']];
                }, array_filter($classes->toArray(), function ($item, $id) use ($keyword) {
                    return strpos($id, $keyword) === 0 || strpos($item['title'], $keyword) === 0;
                }));
                return json(['data' => $items]);
            }
        }
    }

    public function drop_res()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $record = AclResource::where('id', $id)->find();
            if (!$record) {
                return $this->renderError('记录不存在');
            }
            $result = false !== $record->delete();
            if (!$result) {
                return $this->renderError('删除记录失败，请稍后重试');
            }
            return $this->renderSuccess('删除成功', url('backend.authorize_res'));
        }
    }

    public function drop_role()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $record = AclRole::where('id', $id)->find();
            if (!$record) {
                return $this->renderError('记录不存在');
            }
            $result = false !== $record->delete();
            if (!$result) {
                return $this->renderError('删除记录失败，请稍后重试');
            }
            return $this->renderSuccess('删除成功', url('backend.authorize_index'));
        }
    }

    public function drop_rule()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $record = AclUserAclGrant::where('id', $id)->find();
            if (!$record) {
                return $this->renderError('记录不存在');
            }
            $result = false !== $record->delete();
            if (!$result) {
                return $this->renderError('删除记录失败，请稍后重试');
            }
            return $this->renderSuccess('删除成功', url('backend.authorize_rule'));
        }
    }

    public function price()
    {
        $this->assign('page_title', '角色转化价格');
        $this->assign('add_url', url('backend.authorize_add_price'));
        $this->assign('list', AclRolePrice::where('id', '>', 0)->where('valid', 1)->paginate(10));
        $this->assign('del_url', url('backend.authorize_drop_price'));
        $this->assign('edit_uri', 'backend.authorize_edit_price');
        return $this->fetch();
    }

    public function edit_price($id)
    {
        $rule = AclRolePrice::get($id);
        if (!$rule) abort(404, '没有找到指定的价格信息');
        if ($this->request->isPost()) {
            $data = $this->request->post('rule/a');
            /** @var \app\manager\validate\AclRolePrice $validator */
            $validator = Loader::validate('manager/AclRolePrice', 'validate', false, 'manager');
            $result = $validator->scene('edit')->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $rule->startTrans();
            try {
                $result = false !== $rule->save($data);
                if (!$result) {
                    $role->rollback();
                    $this->error($role->getError());
                }
                $rule->commit();
                $this->success('编辑成功', 'backend.authorize_price');
            } catch (Exception $e) {
                $rule->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '编辑角色');
        $this->assign('rule', $rule);
        $this->assign('need_code', !isset($rule['id']));
        return $this->fetch('basic/authorize/add_price');
    }

    public function add_price()
    {
        $rule = new AclRolePrice(['price' => 0, 'valid_seconds' => 86400 * 30, 'deadline' => '1970-01-01T00:00:00']);
        if ($this->request->isPost()) {
            $data = $this->request->post('rule/a');
            /** @var \app\manager\validate\AclRolePrice $validator */
            $validator = Loader::validate('manager/AclRolePrice', 'validate', false, 'manager');
            $result = $validator->check($data);
            if (true !== $result) {
                $this->error($validator->getError());
            }
            $rule->startTrans();
            try {
                $data['valid'] = 1;
                $result = false !== $rule->save($data);
                if (!$result) {
                    $rule->rollback();
                    $this->error($rule->getError());
                }

                $rule->commit();
                $this->success('创建成功', 'backend.authorize_price');
            } catch (Exception $e) {
                $rule->rollback();
                $this->error('数据存储出现异常，请稍后重试');
            }
        }
        $this->assign('page_title', '添加转换规则');
        $this->assign('rule', $rule);
        $this->assign('need_code', !isset($role['id']));
        return $this->fetch('basic/authorize/add_price');
    }

    public function drop_price()
    {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $record = AclRolePrice::where('id', $id)->find();
            if (!$record) {
                return $this->renderError('记录不存在');
            }
            $result = false !== $record->save(['valid' => 0]);
            if (!$result) {
                return $this->renderError('删除记录失败，请稍后重试');
            }
            return $this->renderSuccess('删除成功', url('backend.authorize_price'));
        }
    }
}
