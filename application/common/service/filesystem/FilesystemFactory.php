<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\filesystem;

use app\common\service\filesystem\plugin\MoonpiePlugin;
use app\common\service\filesystem\plugin\ThinkUploadFilePlugin;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemNotFoundException;
use think\Hook;

/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

class FilesystemFactory implements FilesystemFactoryInterface
{
    public function create($code, array $configs)
    {
        switch ($code) {
            case 'local':
                $adapter = new Local(ROOT_PATH . 'public' . DS . trim($configs['base path'], '\\/') . DS);
                break;
            default:
                $adapter = null;
                break;
        }
        $params = compact('code', 'configs', 'adapter');
        Hook::listen('init_system_filesystem_adapter', $params);
        $final = $params['adapter'];
        if (is_null($final)) {
            throw new FilesystemNotFoundException(sprintf('指定的文件系统（code:%s）不存在', $code));
        }
        $filesystem = new Filesystem($adapter, $configs);
        //添加默认的插件
        $filesystem->addPlugin(new ThinkUploadFilePlugin());
        $filesystem->addPlugin(new MoonpiePlugin());
        $params['filesystem'] = $filesystem;
        Hook::listen('init_system_filesystem_manager', $filesystem);
        return $params['filesystem'];
    }
}
