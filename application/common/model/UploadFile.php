<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\model;

use app\common\service\helper\ArrayHelper;
use EasyWeChat\Kernel\Support\Arr;
use think\Request;
use traits\model\SoftDelete;

/**
 * 文件库模型
 * Class UploadFile
 * @package app\common\model
 */
class UploadFile extends BaseModel
{
    use SoftDelete;

    protected $table = 'system_upload_file';
    protected $updateTime = false;
    protected $deleteTime = false;
    protected $append = ['file_path'];
    protected $filesystem;

    /**
     * 获取图片完整路径
     * @param $value
     * @param $data
     * @return string
     */
    public function getFilePathAttr($value, $data)
    {
        $storage = app('storage.manager');
        $domain = $data['file_url'];
        if (empty($domain)) $domain = $storage->getVisitDomain($data['storage']);
        return $domain  . $data['file_name'];
        /*if ($data['storage'] === 'local') {
            return self::$base_url . 'uploads/' . $data['file_name'];
        }
        $prefix = Setting::getS
        return $data['file_url'] . '/' . $data['file_name'];*/
    }

    /**
     * 根据文件名查询文件id
     * @param $fileName
     * @return mixed
     */
    public static function getFildIdByName($fileName)
    {
        return (new static)->where(['file_name' => $fileName])->value('file_id');
    }

    /**
     * 查询文件id
     * @param $fileId
     * @return mixed
     */
    public static function getFileName($fileId)
    {
        return static::where(['file_id' => $fileId])->value('file_name');
    }

    /**
     * 获取列表记录
     * @param $group_id
     * @param string $file_type
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList($group_id, $file_type = 'image', array $scopeConfig = null)
    {
        $model = $this->where(['file_type' => $file_type]);
        if (!empty($scopeConfig)) {
            $valid_scopes = Arr::get($scopeConfig, 'valid', []);
            if (!empty($valid_scopes)) $model->whereIn('scope', $valid_scopes);
        }
        $model->where('is_delete', 0);
        if ($group_id !== -1) {
            $model->where(compact('group_id'));
        }
        return $model->order(['file_id' => 'desc'])
            ->paginate(32, false, [
                'query' => Request::instance()->request()
            ]);
    }
    /**
     * 添加新记录
     * @param $data
     * @return false|int
     */
    public function add($data)
    {
        return $this->save($data);
    }

    /**
     * @todo 后续需要能够同步删除文件
     * 批量软删除
     * @param $fileIds
     * @return $this
     */
    public function softDelete($fileIds)
    {
        return $this->where('file_id', 'in', $fileIds)->update(['is_delete' => 1, 'delete_at' => time()]);
    }

    /**
     * 批量移动文件分组
     * @param $group_id
     * @param $fileIds
     * @return $this
     */
    public function moveGroup($group_id, $fileIds)
    {
        return $this->where('file_id', 'in', $fileIds)->update(compact('group_id'));
    }
    /**
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getFilesystem()
    {
        if (is_null($this->filesystem)) {
            list(, $this->filesystem) = $this->storageManager = app('storage.manager')->getByCode($this->getData('storage'));
        }
        return $this->filesystem;
    }
    protected static function init()
    {
        parent::init();
        static::afterUpdate(function ($record) {
            /**  @var static $record */
            if ($record->getData('is_delete')) {
                $record->getFilesystem()->delete($record['file_name']);
            }
        });
    }
    public function getMapData()
    {
        return [
            'file_id' => $this->getData('file_id'),
            'file_path' => $this->file_path,
        ];
    }
    public static function addUploadFile($storageCode, $groupId, $fileName, $fileInfo, $fileType, $scope)
    {
        // 存储域名
        $file_domain = isset($fileInfo['domain']) ? $fileInfo['domain'] : '';
        // 添加文件库记录
        $model = new static;
        $model->add([
            'group_id' => $groupId > 0 ? (int) $groupId : 0,
            'storage' => $storageCode,
            'file_url' => $file_domain,
            'file_name' => str_replace('\\', '/', $fileName),
            'file_size' => $fileInfo['size'],
            'file_type' => $fileType,
            'scope' => $scope, 'is_delete' => 0,
            'extension' => pathinfo($fileInfo['path'], PATHINFO_EXTENSION),
        ]);
        return $model;
    }
    public function getId()
    {
        return $this->getData('file_id');
    }
    public function getContentHeader($name = null)
    {
        $headers = [
            'Content-Type' => implode('/', [$this->getData('file_type'), $this->getData('extension')])
        ];
        if(is_null($name)) return $headers;
        return ArrayHelper::getValue($headers, $name);
    }
    public function getBinaryContent()
    {
        return $this->getFilesystem()->read($this->getData('file_name'));
    }
}
