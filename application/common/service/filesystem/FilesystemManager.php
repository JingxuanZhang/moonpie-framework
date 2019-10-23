<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem;

use EasyWeChat\Kernel\Support\Arr;
use League\Flysystem\FilesystemNotFoundException;

/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

class FilesystemManager
{
    private $configs = [];
    private $filesystems = [];
    private $factory;
    private $visitManager;
    public function __construct(FilesystemFactoryInterface $factory, VisitManagerInterface $visitManager, array $configs = [])
    {
        $this->factory = $factory;
        $this->configs = $configs;
        $this->visitManager = $visitManager;
    }
    /**
     * 获取默认的文件系统
     * @param bool $init 是否初始化默认引擎
     * @return array|string [code, \League\Flysystem\FilesystemInterface ]
     */
    public function getDefault($init = true)
    {
        $default_code = Arr::get($this->configs, 'default');
        $filesystem = null;
        if ($default_code && Arr::has($this->filesystems, $default_code)) {
        }else {
            if($init) $filesystem = $this->factory->create($default_code, $this->configs['engines'][$default_code]);
        }
        if ($init) return [$default_code, $this->filesystems[$default_code] = $filesystem];
        return $default_code;
    }
    /**
     * 根据code获取系统支持的文件系统
     * @param string $code 引擎机读玛
     * @param array $config 引擎配置
     * @param bool $force 是否强制配置，而不管是否已加载
     * @throws FilesystemNotFoundException
     * @return array [code, \League\Flysystem\FilesystemInterface ]
     */
    public function getByCode($code, array $config = [], $force = false)
    {
        if (!isset($this->filesystems[$code]) || $force) {
            $default_config = Arr::get($this->configs, "engines.{$code}", []);
            if (empty($default_config)) throw new FilesystemNotFoundException("can not found filesystem('{$code}')");
            $this->filesystems[$code] = $this->factory->create($code, array_merge($default_config, $config));
        }
        return [$code, $this->filesystems[$code]];
    }
    public function setDriverConfig(array $configs)
    {
        $this->configs = $configs;
        return $this;
    }
    public function getVisitDomain($code, $overrideConfig = [])
    {
        return $this->getVisitManager()->getDomain($code, array_merge(Arr::get($this->configs, "engines.{$code}"), $overrideConfig));
    }
    public function getVisitManager()
    {
        return $this->visitManager;
    }
    public function getEngineByConfig(array $config)
    {
        $code = Arr::get($config, 'code', $this->getDefault(false));
        $engine_config = Arr::get($config, 'config', []);
        return $this->getByCode($code, $engine_config, true);
    }
}
