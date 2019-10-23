<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\model;

use EasyWeChat\Kernel\Support\Arr;
use think\Cache;
use think\Hook;

/**
 * 系统设置模型
 * Class Setting
 * @package app\common\model
 */
class Setting extends BaseModel
{
    protected $table = 'site_setting';
    protected $createTime = false;

    /**
     * 获取器: 转义数组格式
     * @param $value
     * @return mixed
     */
    public function getValuesAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * 修改器: 转义成json格式
     * @param $value
     * @return string
     */
    public function setValuesAttr($value)
    {
        return json_encode($value);
    }

    /**
     * 获取指定项设置
     * @param $key
     * @param $wxapp_id
     * @return array
     */
    public static function getItem($key)
    {
        $data = self::getAll();
        return isset($data[$key]) ? $data[$key]['values'] : [];
    }

    /**
     * 获取设置项信息
     * @param $key
     * @return null|static
     * @throws \think\exception\DbException
     */
    public static function detail($key)
    {
        return self::get(compact('key'));
    }

    /**
     * 全局缓存: 系统设置
     * @param null $wxapp_id
     * @return array|mixed
     */
    public static function getAll()
    {
        $self = new static;
        if (!$data = Cache::get('site_setting')) {
            $data = array_column(collection($self::all())->toArray(), null, 'key');
            Cache::set('site_setting', $data);
        }
        return array_merge_multiple(static::defaultData(), $data);
    }

    /**
     * 默认配置
     * @return array
     */
    public static function defaultData()
    {
        $return = [
            'store' => [
                'key' => 'site',
                'describe' => '站点设置',
                'values' => ['name' => '甜心网络服务'],
            ],
            'storage' => [
                'key' => 'storage',
                'describe' => '上传设置',
                'values' => [
                    'default' => 'local',
                    'engines' => [
                        'qiniu' => [
                            'bucket' => '',
                            'access_key' => '',
                            'secret_key' => '',
                            'domain' => 'http://',
                            'label' => [
                                'title' => '七牛云存储',
                                'description' => '使用七牛云存储资源'
                            ],
                        ],
                        'local' => [
                            'base path' => '/uploads',
                            'label' => [
                                'title' => '本地文件',
                                'description' => '本地文件管理处理',
                            ],
                            'permissions' => [
                                'file' => '0755',
                                'dir' => '0777',
                            ]
                        ]
                    ]
                ],
            ],


        ];
        Hook::listen('alter_site_setting_keys', $return);
        return $return;
    }
    /**
     * 更新系统设置
     * @param $key
     * @param $values
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function edit($key, $values)
    {
        $record = self::detail($key);
        // 删除系统设置缓存
        Cache::rm('site_setting');
        if ($record) {
            return $record->save(['values' => $values]) !== false;
        } else {
            $model = new static();
            return $model->save([
                'key' => $key,
                'describe' => static::getKeyDescribe($key),
                'values' => $values,
            ]) !== false;
        }
    }
    protected static function getKeyDescribe($key)
    {
        return Arr::get(static::defaultData(), "{$key}.describe", '未知描述');
    }
    /**
     * @return \app\common\service\filesystem\FilesystemManager
     */
    public static function getStorage()
    {
        static $storage;
        if (is_null($storage)) {
            $config = static::getItem('storage');
            $storage = app('storage.manager');
            //$storage->setDrivegzrConfig($config);
        }
        return $storage;
    }
}
