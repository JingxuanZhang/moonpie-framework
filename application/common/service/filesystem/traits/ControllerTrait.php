<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem\traits;

use app\common\model\UploadFile;
use app\common\model\UploadGroup;

/**
 * 封装操作相关的控制器方法，
 * 使用者应当提供保护属性$groupScopeConfig=['manage' => 'some scope', 'valid' => ['some scope one', 'some scope two']]
 */
trait ControllerTrait
{
    /**
     * 文件库列表
     * @param string $type
     * @param int $group_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fileList($type = 'image', $group_id = -1)
    {
        // 分组列表
        $group_list = (new UploadGroup)->getList($type, $this->groupScopeConfig);
        // 文件列表
        $file_list = (new UploadFile)->getList(intval($group_id), $type, $this->groupScopeConfig);
        return $this->renderSuccess('success', '', compact('group_list', 'file_list'));
    }

    /**
     * 新增分组
     * @param $group_name
     * @param string $group_type
     * @return array
     */
    public function addGroup($group_name, $group_type = 'image')
    {
        $model = new UploadGroup;
        $scope = $this->groupScopeConfig['manage'];
        if ($model->add(compact('group_name', 'group_type', 'scope'))) {
            $group_id = $model->getLastInsID();
            return $this->renderSuccess('添加成功', '', compact('group_id', 'group_name'));
        }
        $error = $model->getError() ?: '添加失败';
        return $this->renderError($error);
    }

    /**
     * 编辑分组
     * @param $group_id
     * @param $group_name
     * @return array
     * @throws \think\exception\DbException
     */
    public function editGroup($group_id, $group_name)
    {
        $model = UploadGroup::detail($group_id);
        if ($model->edit(compact('group_name'))) {
            return $this->renderSuccess('修改成功');
        }
        $error = $model->getError() ?: '修改失败';
        return $this->renderError($error);
    }

    /**
     * 删除分组
     * @param $group_id
     * @return array
     * @throws \think\exception\DbException
     */
    public function deleteGroup($group_id)
    {
        $model = UploadGroup::detail($group_id);
        if ($model->remove()) {
            return $this->renderSuccess('删除成功');
        }
        $error = $model->getError() ?: '删除失败';
        return $this->renderError($error);
    }

    /**
     * 批量删除文件
     * @param $fileIds
     * @return array
     */
    public function deleteFiles($fileIds)
    {
        $model = new UploadFile;
        if ($model->softDelete($fileIds)) {
            return $this->renderSuccess('删除成功');
        }
        $error = $model->getError() ?: '删除失败';
        return $this->renderError($error);
    }

    /**
     * 批量移动文件分组
     * @param $group_id
     * @param $fileIds
     * @return array
     */
    public function moveFiles($group_id, $fileIds)
    {
        $model = new UploadFile;
        if ($model->moveGroup($group_id, $fileIds) !== false) {
            return $this->renderSuccess('移动成功');
        }
        $error = $model->getError() ?: '移动失败';
        return $this->renderError($error);
    }
    /**
     * 添加文件库上传记录
     * @param $storageCode
     * @param $groupId
     * @param $fileName
     * @param $fileInfo
     * @param $fileType
     * @param $scope
     * @return UploadFile
     */
    private function addUploadFile($storageCode, $groupId, $fileName, $fileInfo, $fileType, $scope)
    {
        return UploadFile::addUploadFile($storageCode, $groupId, $fileName, $fileInfo, $fileType, $scope);
    }
}
