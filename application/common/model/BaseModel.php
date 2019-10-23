<?php

/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by Moonpie Studio
 * User: Administrator
 * Date: 2017/2/5
 * Time: 15:39
 */

namespace app\common\model;


use think\Model;

abstract class BaseModel extends Model
{
    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $autoWriteTimestamp = true;
    protected static $base_url;

    /**
     * 模型基类初始化
     */
    protected static function init()
    {
        parent::init();
        // 获取当前域名
        self::$base_url = base_url();
    }

    public function setModelError($error)
    {
        $this->error = $error;
    }

    /**
     * 自动写入时间戳
     * @access public
     * @param string $name 时间戳字段
     * @return mixed
     */
    protected function autoWriteTimestamp($name)
    {
        if (isset($this->type[$name])) {
            $type = $this->type[$name];
            if (strpos($type, ':')) {
                list($type, $param) = explode(':', $type, 2);
            }
            switch ($type) {
                case 'datetime':
                case 'date':
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value = $this->formatDateTime(time(), $format);
                    break;
                case 'timestamp':
                case 'integer':
                default:
                    $value = time();
                    break;
            }
        } elseif (
            is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
                'datetime',
                'date',
                'timestamp',
            ])
        ) {
            $value = $this->formatDateTime(time(), $this->dateFormat);
        } else {
            $value = $this->formatDateTime(time(), $this->dateFormat, true);
        }
        return $value;
    }

    public function getCreateAtAttr($attr)
    {
        if (is_integer($attr)) {
            return new \DateTime(date('Y-m-d H:i:s', $attr));
        }
        return new \DateTime($attr);
    }

    public function getUpdateAtAttr($attr)
    {
        if (is_integer($attr)) {
            return new \DateTime(date('Y-m-d H:i:s', $attr));
        }
        return new \DateTime($attr);
    }
    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * 获取模型的验证器信息
     */
    public function getValidator()
    {
        throw new \InvalidArgumentException('you should declare the validator method by yourself.');
    }
}
