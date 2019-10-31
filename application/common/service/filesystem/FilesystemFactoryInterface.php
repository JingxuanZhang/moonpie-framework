<?php
/*
 *  Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
*/

namespace app\common\service\filesystem;

interface FilesystemFactoryInterface
{
    /**
     * 根据机读码和配置创建一个文件系统
     * @param string 文件系统机读码
     * @param array $config 文件系统配置
     * @return \League\Flysystem\FilesystemInterface
     */
    public function create($code, array $config);
}
