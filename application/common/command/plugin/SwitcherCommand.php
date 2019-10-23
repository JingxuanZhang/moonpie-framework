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

class SwitcherCommand extends Command
{
    protected function configure()
    {
        $this->setName('mp:plugin')
            ->addArgument('action', Argument::REQUIRED, 'plugin action')
            ->addArgument('names', Argument::IS_ARRAY, 'plugin names')
            ->setDescription('manage plugin install, uninstall, upgrade,cache-clear,list')
            ->setHelp($this->getHelpText())
            ->addOption('force', 'f', Option::VALUE_NONE, 'whether force run');
    }
    protected function getHelpText()
    {
        return <<<EOT
`php think mp:plugin list` show system plugins
`php think mp:plugin cache-clear` remove plugins cache 
EOT;

    }
    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        $names = $input->getArgument('names');
        $force = $input->getOption('force');
        $target = 'plugin';
        switch ($action) {
            case 'install':
                $method = 'install';
                break;
            case 'upgrade':
                $method = 'upgrade';
                break;
            case 'uninstall':
                $method = 'uninstall';
                break;
            case 'cache-clear':
                $method = 'refreshPluginCache';
                $target = 'manager';
                break;
            case 'list': //展示插件信息
                return $this->handleListAction();
            default:
                $output->error(sprintf('unsupported action "%s"', $action));
                return 200;
        }
        /** @var \app\common\service\plugin\PluginManager $manager */
        $manager = $this->getConsole()->getPluginManager();
        if ($target == 'plugin') {
            try {
                /** @var \app\common\service\plugin\PluginElement[] $plugins */
                $plugins = call_user_func([$manager, $method], $names, $force);
                if (empty($plugins)) {
                    $output->warning(sprintf('handle plugin name(s) %s is empty.', implode(',', $names)));
                    return 2;
                }
                foreach ($plugins as $plugin_element) {
                    $output->info(sprintf('%s plugin successfully, code: %s, title: %s.', $action, $plugin_element->getCode(), $plugin_element->getTitle()));
                }
            } catch (\Exception $e) {
                $output->error(sprintf(
                    '%s plugin failed with exception, code: %s, title: %s, at file: %s and line: %d',
                    $action,
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));
                throw $e;
            }
        } else if ($target == 'manager') {
            $result = call_user_func([$manager, $method]);
            if ($result) {
                $output->info(sprintf('%s plugin successfully.', $action));
            }
        }
    }
    protected function handleListAction()
    {
        /** @var \app\common\service\plugin\PluginManager $manager */
        $manager = $this->getConsole()->getPluginManager();
        $plugins = $manager->getPlugins(false);
        $tmpl = '|%-10s|%-10s|%-10s|%-10s|';
        $this->output->writeln(sprintf($tmpl, 'Code', 'Name', 'Description', 'Enabled'));
        /**
         * @var string $code
         * @var \app\common\service\plugin\PluginElement $element
         */
        foreach($plugins as $code => $element) {
            $this->output->writeln(sprintf(
                $tmpl,
                $code, $element->getTitle(), $element->getElement('description'),
                $element->getElement('enabled', false)
            ));
        }
    }
}
