<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\controller\upload;

use app\common\model\Setting as SettingModel;
use app\common\service\data\ArrayDataProvider;
use app\common\service\filesystem\exception\EngineControlNotFoundException;
use app\common\service\filesystem\traits\ControllerTrait;
use app\manager\controller\Base;

/**
 * 文件库管理
 * Class Upload
 * @package app\store\controller
 */
class Upload extends Base
{
    use ControllerTrait;
    protected $groupScopeConfig = [
        'manage' => 'admin',
        'valid' => ['admin', ''],
    ];

    /**
     * 图片上传接口
     * @param int $group_id
     * @return array
     * @throws \think\Exception
     */
    public function image($group_id = -1)
    {
        // 实例化存储驱动
        $storage_manager = SettingModel::getStorage();
        list($storage_code, $storage) = $storage_manager->getDefault();
        /**@var \think\File $file */
        $file = $this->request->file('iFile');
        // 上传图片
        $file->validate(['size' => 4 * 1024 * 1024, 'ext' => 'jpg,jpeg,png,gif']);
        // 图片上传路径
        $file_name = $storage->invokeThinkUpload('getThinkFileSavePath', [$file]);
        /**@var \think\File|string $upload_file */
        $upload_file = $storage->invokeThinkUpload('uploadThinkFile', [$file, $file_name, true]);
        if (is_string($upload_file)) return json(['code' => 0, 'msg' => '图片上传失败' . $upload_file]);
        // 图片信息
        // 添加文件库记录
        $final_file = $this->addUploadFile($storage_code, $group_id, $file_name, $upload_file, 'image', $this->groupScopeConfig['manage']);
        // 图片上传成功
        return json(['code' => 1, 'msg' => '图片上传成功', 'data' => $final_file]);
    }

    public function storageConfig()
    {
        //列出所有的文件配置信息
        $configs = SettingModel::getItem('storage');
        $data_provider = new ArrayDataProvider([
            'paginationConfig' => [
                'listRows' => 10
            ],
            'allModels' => $configs['engines'],
        ]);
        $data_provider->prepare();
        $vars = [
            'page_title' => '文件存储引擎配置',
            'data_provider' => $data_provider,
            'default_engine' => $configs['default'],
            'edit_uri' => 'backend.basic_upload_storage_config',
        ];
        $this->assign($vars);
        return $this->fetch();
    }
    /**
     * 配置具体的引擎
     */
    public function driverConfig($code)
    {
        /**@var \app\common\service\filesystem\control\ControlEngineInterface $manager */
        $manager = app('storage.engine_manager');
        try {
            return $manager->handleEngineManage($code, $this->request);
        }catch (EngineControlNotFoundException $e) {
            $this->error($e->getMessage());
        }
    }
}
