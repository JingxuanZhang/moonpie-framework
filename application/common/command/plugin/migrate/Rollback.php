<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\command\plugin\migrate;

use think\console\Input;
use think\console\input\Option as InputOption;
use think\console\Output;

class Rollback extends Migrate
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mp:plugin-migrate-rollback')
            ->setDescription('Migrate rollback plugin database')
            ->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to migrate to')
            ->addOption('target', 't', InputOption::VALUE_OPTIONAL, 'target migration version')
            ->setHelp(<<<EOT
The <info>mp:plugin-migrate:rollback</info> command rollback all available migrations, optionally down to a specific version

<info>php think --plugin=some_xxx mp:plugin-migrate-rollback</info>
<info>php think --plugin=some_xxx mp:plugin-migrate-rollback -d 20110103</info>
<info>php think --plugin=some_xxx mp:plugin-migrate-rollback -v</info>

EOT
            );
    }

    /**
     * Migrate the database.
     *
     * @param Input $input
     * @param Output $output
     * @return integer integer 0 on success, or an error code.
     */
    protected function execute(Input $input, Output $output)
    {
        $date = $input->getOption('date');

        // run the migrations
        $start = microtime(true);
        $from = $this->getCurrentVersion();
        $to = $this->getTargetVersion();
        if (null !== $date) {
            $this->rollbackToDateTime(new \DateTime($date));
        } else {
            $final_migrations = $this->findByMigrationVersion($input->getOption('target'));
            $this->displayExecuteResult($this->migrationManager->handleMigration($final_migrations, $this->getTargetVersion(), $this->getCurrentVersion(), [], '>'));
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }

    protected function findByMigrationVersion($version)
    {
        $migrations = $this->getMigrations();
        if (empty($version)) {
            $from = $this->getCurrentVersion();
            $to = $this->getTargetVersion();
            $target_version = max(array_keys($migrations));
            if ($target_version > $to) $target_version = $to;
            if ($target_version < $from) $target_version = $from;
            if (isset($migrations[$target_version])) {
                $final_version = max(array_keys($migrations[$target_version]));
                return [
                    $target_version => [
                        $final_version => $migrations[$target_version][$final_version]
                    ]
                ];
            }
        } else {
            $final = [];
            foreach ($migrations as $plugin_version => $items) {
                foreach ($items as $migration_version => $migration) {
                    if ($migration_version >= $version) {
                        if (!isset($final[$plugin_version])) {
                            $final[$plugin_version] = [];
                        }
                        $final[$plugin_version][$migration_version] = $migration;
                    }
                }
            }
        }
        return $final;
    }

    public function rollbackToDateTime(\DateTime $dateTime)
    {
        $migrations = $this->getMigrations();
        $dateString = $dateTime->format('YmdHis');

        $final_migrations = [];
        foreach ($migrations as $plugin_version => $migration_items) {
            $outstanding_items = array_filter($migration_items, function ($version) use ($dateString) {
                return $version >= $dateString;
            });
            if (!empty($outstanding_items)) {
                $final_migrations[$plugin_version] = $outstanding_items;
            }
        }

        if (count($final_migrations) > 0) {
            $this->output->writeln('Migrating to version ');
            $executed = $this->migrationManager->handleMigration($final_migrations, $this->getTargetVersion(), $this->getCurrentVersion(), [], '>');
            $this->displayExecuteResult($executed);

        }
    }

    protected function displayExecuteResult($executed)
    {
        if (!empty($executed)) {
            foreach ($executed as $plugin_version => $items) {
                $this->output->writeln("<comment>Will migration plugin version: {$plugin_version}</comment>");
                foreach ($items as $scope => $scope_items) {
                    if (!empty($scope_items)) {
                        $this->output->info("<highlight>Will execute action  {$scope}</highlight>");
                        foreach ($scope_items as $scope_item) {
                            $this->output->highlight('== migration to version: ' . $scope_item);
                        }
                    }
                }
            }
        }
    }
}
