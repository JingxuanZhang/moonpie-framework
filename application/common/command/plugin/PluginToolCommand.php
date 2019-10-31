<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\command\plugin;


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
            ->addArgument('action', Argument::REQUIRED, 'action name, support: menu-clear,asset-clear,migration-run,migration-rollback,migration-breakpoint')
            ->addOption('plugin', null, Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'plugin which will clear asset, migration for', [])
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
            default:
                $output->error(sprintf('no support action: %s', $action));
                break;
        }
    }

    /**
     * 重置菜单数据
     */
    protected function handleMenuClear()
    {
        /** @var \app\common\service\menu\MenuService $service */
        $service = app('menu.manager');
        $service->reset();
        $this->output->info('clear system menu cache successfully');
    }
    protected function handleAssetClear()
    {
        /** @var \app\common\service\plugin\asset\AssetManager $service*/
        $service = app('asset.manager');
        $service->rebuild();
        $this->output->info('rebuild asset cache successfully');
    }
}