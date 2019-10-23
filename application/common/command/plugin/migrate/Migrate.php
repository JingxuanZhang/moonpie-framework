<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\command\plugin\migrate;

use app\common\service\plugin\migration\MigrationManager;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\migration\Command;

abstract class Migrate extends Command
{
    /**
     * @var array
     */
    protected $migrations;
    /**
     * @var MigrationManager
     */
    protected $migrationManager;

    public function __construct($name = null)
    {

        parent::__construct($name);
        $this->addOption('--config', null, Option::VALUE_REQUIRED, 'The database config name', 'database');
        $this->addOption('plugin', 'p', Option::VALUE_REQUIRED, 'give your plugin code');
        $this->addOption('from', null, Option::VALUE_OPTIONAL, 'from plugin version');
        $this->addOption('to', null, Option::VALUE_OPTIONAL, 'to plugin version');
    }

    /**
     * 初始化
     * @param Input $input An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {
        $this->config = $input->getOption('config');
        //这里找到指定的插件
        /** @var \app\common\service\plugin\PluginManager $manager */
        $manager = app('plugin.manager');
        $code = $input->getOption('plugin');
        $plugin = $manager->getPluginByCode($code);
        if (!$plugin) {
            throw new \LogicException(sprintf('please make sure your plugin code "%s" is valid.', $code));
        }
        $this->migrationManager = new MigrationManager($manager, $plugin);
    }
    protected function getPath()
    {
        return $this->migrationManager->getPluginElement()->getRootPath() . 'migrations' . DS;
    }
    protected function getCurrentVersion()
    {
        $version = $this->input->getOption('from');
        if(empty($version)) {
            return $this->migrationManager->getPluginElement()->getVersion();
        }
        return $version;
    }
    protected function getTargetVersion()
    {
        $version = $this->input->getOption('to');
        if(empty($version)) {
            return $this->migrationManager->getPluginElement()->getVersion();
        }
        return $version;
    }
    protected function getMigrations()
    {
        return $this->migrationManager->getMigrations();
    }
}
