<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\command\plugin;


use app\common\service\plugin\PluginElement;
use mysql_xdevapi\Warning;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class PluginToolCommand extends Command
{
    protected function configure()
    {
        $this->setName('mp:plugin-tool')
            ->setDescription('help developer manage plugin')
            ->addArgument('action', Argument::REQUIRED, 'action name, support: menu-clear,asset-clear,acl-reset')
            ->addOption('plugin', 'p', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'plugin which will clear asset, menu, acl for', [])
            ->setHelp(<<<EOT
php think mp:plugin-tool menu-clear <comment>菜单缓存清除</comment>
php think mp:plugin-tool asset-clear <comment>前端资源重置</comment>
php think mp:plugin-tool acl-reset<comment>权限资源重置</comment>
EOT
)
            ;
    }
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        switch ($action) {
            case 'menu-clear':
                return $this->handleMenuClear();
            case 'asset-clear':
                return $this->handleAssetClear();
            case 'acl-reset':
                return $this->handleAclReset();
            default:
                $output->error(sprintf('no support action: %s', $action));
                break;
        }
    }
    protected function handleMenuClear()
    {
        /** @var \app\common\service\menu\MenuService $service */
        $service = app('menu.manager');
        $service->reset();
        $this->output->info('clear system menu cache successfully');
    }
    /**
     * 升级插件中的角色信息
     */
    protected function handleAclReset()
    {
        $plugin_codes = $this->input->getOption('plugin');
        if(empty($plugin_codes)) {
            $this->output->warning('安全起见,请选择需要重置权限资源的插件信息');
            return 0;
        }
        /** @var \app\common\service\plugin\acl\AclManager $service */
        $service = app('plugin.acl_manager');
        $plugins = $service->getPluginManager()->getPlugins();
        /**
         * @var  $code
         * @var  $plugin PluginElement
         */
        foreach($plugins as $code => $plugin) {
            if(in_array($code, $plugin_codes)) {
                $result = $service->install($plugin, true);
                if(!$result) {
                    $this->output->warning(sprintf('插件(code: %s, title: %s)重新安装权限角色信息失败', $code, $plugin->getTitle()));
                }else {
                    $this->output->info(sprintf('插件(code: %s, title: %s)重新安装权限角色信息失败', $code, $plugin->getTitle()));
                }
            }
        }
    }
    protected function handleAssetClear()
    {
        /** @var \app\common\service\plugin\asset\AssetManager $service*/
        $service = app('asset.manager');
        $service->rebuild();
        $this->output->info('rebuild asset cache successfully');
    }
}