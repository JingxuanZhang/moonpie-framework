<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\manager\service\filesystem\control;


use app\common\model\Setting;
use app\common\service\filesystem\control\EngineHandlerInterface;
use app\manager\validate\LocalUploadConfig;
use EasyWeChat\Kernel\Support\Arr;
use think\exception\ValidateException;
use think\Request;
use think\View;

class LocalHandler implements EngineHandlerInterface
{
    public function support($code)
    {
        return $code == 'local';
    }

    public function handle(Request $request)
    {
        $config = $this->getStorageConfig('local');
        if ($request->isPost()) {
            //处理数据
            $setting = $request->post('config/a', []);
            //现在验证配置
            //首先如果是默认引擎的话不能将其切换成非默认
            if ($config['active'] && !$setting['active']) {
                return json(['code' => 0, 'msg' => '默认引擎无法卸下，请先将其他引擎设为默认引擎']);
            }
            $validator = new LocalUploadConfig();
            $result = $validator->check($setting);
            if (!$result) {
                return json(['code' => 0, 'msg' => $validator->getError()]);
            }
            //开始存储配置
            $configs = $this->getStorageConfig();
            if ($setting['active']) $configs['default'] = 'local';
            //存储配置
            $configs['engines']['local'] = $setting['setting'];
            Setting::edit('storage', $configs);
            return json(['code' => 1, 'msg' => '修改配置成功']);
        }
        $vars = [
            'page_title' => '配置' . Arr::get($config, 'setting.label.title', '本地系统'),
            'init_config' => $config,
        ];
        return view('upload/upload/storage_local', $vars);
    }

    protected function getStorageConfig($code = null)
    {
        $config = Setting::getItem('storage');
        if (!empty($code)) {
            return [
                'active' => strval($config['default'] == $code),
                'setting' => Arr::get($config, "engines.{$code}", []),
            ];
        }
        return $config;
    }

    public function renderViewForExternal(Request $request, array $viewConfig)
    {
        $default = [
            'form_field' => 'please define your form field',
            'id' => 'storage_config_container',
            'config' => $this->getStorageConfig('local')['setting'],
        ];
        $config = $default + $viewConfig;
        return View::instance()->fetch(__DIR__ . '/view/local_engine_config.html', $config);
    }

    public function handleExternalRequest(Request $request, $formField)
    {
        //处理数据
        list($field, $scope) = explode(':', $formField);
        $config = $request->post("{$field}/a", []);
        $setting = Arr::get($config, $scope, []);
        //现在验证配置
        //首先如果是默认引擎的话不能将其切换成非默认
        $validator = new LocalUploadConfig();
        $result = $validator->onlySetting()->check(compact('setting'));
        if (!$result) {
            throw new ValidateException($validator->getError());
        }
        return $setting;
    }

}