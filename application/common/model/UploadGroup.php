<?php
/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

namespace app\common\model;

use EasyWeChat\Kernel\Support\Arr;

/**
 * 文件库分组模型
 * Class UploadGroup
 * @package app\common\model
 */
class UploadGroup extends BaseModel
{
    protected $table = 'system_upload_group';

    /**
     * 分组详情
     * @param $group_id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function detail($group_id)
    {
        return self::get($group_id);
    }
    /**
     * 获取列表记录
     * @param string $group_type
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($group_type = 'image', array $scopeConfig = null)
    {
        $query = $this->where(compact('group_type'));
        if (!empty($scopeConfig)) {
            $valid_scopes = Arr::get($scopeConfig, 'valid', []);
            if (!empty($valid_scopes)) {
                $query->whereIn('scope', $valid_scopes);
            }
        }
        return $query->order(['sort' => 'asc'])->select();
    }

    /**
     * 添加新记录
     * @param $data
     * @return false|int
     */
    public function add($data)
    {
        $data['sort'] = 100;
        return $this->save($data);
    }

    /**
     * 更新记录
     * @param $data
     * @return bool|int
     */
    public function edit($data)
    {
        return $this->allowField(true)->save($data) !== false;
    }

    /**
     * 删除记录
     * @return int
     */
    public function remove()
    {
        // 更新该分组下的所有文件
        $model = new UploadFile;
        $model->where(['group_id' => $this['group_id']])->update(['group_id' => 0]);
        return $this->delete();
    }
}
