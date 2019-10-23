<?php
/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

namespace app\common\model;

/**
 * 已上传文件使用记录表MO型
 * Class UploadFileUsed
 * @package app\common\model
 */
class UploadFileUsed extends BaseModel
{
    protected $table = 'system_upload_file_used';
    protected $updateTime = false;

    /**
     * 新增记录
     * @param $data
     * @return false|int
     */
    public function add($data)
    {
        return $this->save($data);
    }

    /**
     * 移除记录
     * @param $from_type
     * @param $file_id
     * @param null $from_id
     * @return int
     */
    public function remove($from_type, $file_id, $from_id = null)
    {
        $where = compact('from_type', 'file_id');
        !is_null($from_id) && $where['from_id'] = $from_id;
        return $this->where($where)->delete();
    }
}
