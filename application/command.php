<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

return [
    'app\common\command\MaintenanceCommand',
    'app\common\command\plugin\SwitcherCommand', //插件升级安装卸载管理
    'app\common\command\plugin\PluginToolCommand',//插件一些数据维护管理
    'app\common\command\plugin\migrate\Create',//插件数据创建
    'app\common\command\plugin\migrate\Run',//插件数据迁移执行
    'app\common\command\plugin\migrate\Rollback',//插件数据迁移回滚
];
