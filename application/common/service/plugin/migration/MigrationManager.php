<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\plugin\migration;


use app\common\model\PluginMigrations;
use app\common\service\plugin\exception\InvalidArgumentException;
use app\common\service\plugin\PluginElement;
use app\common\service\plugin\PluginManager;
use Phinx\Db\Adapter\AdapterFactory;
use Phinx\Db\Adapter\ProxyAdapter;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Util;
use think\Db;
use think\migration\Migrator;

class MigrationManager
{
    protected $pluginManager;
    protected $pluginElement;

    public function __construct(PluginManager $pluginManager, PluginElement $pluginElement)
    {
        $this->pluginManager = $pluginManager;
        $this->pluginElement = $pluginElement;
    }

    public function getPluginElement()
    {
        return $this->pluginElement;
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }

    /**
     * @var array
     */
    protected $migrations;

    public function install($force = false)
    {
        $this->migrate(null, $force);
    }

    public function uninstall($force = false)
    {
        $this->migrate(0, $force);
    }

    public function upgrade($force = false)
    {
        $this->migrateUpgrade($this->pluginElement->getVersion(), $force);
    }

    public function getAdapter()
    {
        if (isset($this->adapter)) {
            return $this->adapter;
        }

        $options = $this->getDbConfig();

        $adapter = AdapterFactory::instance()->getAdapter($options['adapter'], $options);

        if ($adapter->hasOption('table_prefix') || $adapter->hasOption('table_suffix')) {
            $adapter = AdapterFactory::instance()->getWrapper('prefix', $adapter);
        }

        $this->adapter = $adapter;

        return $adapter;
    }

    /**
     * 获取数据库配置
     * @return array
     */
    protected function getDbConfig()
    {
        $config = Db::connect('database')->getConfig();

        if ($config['deploy'] == 0) {
            $dbConfig = [
                'adapter' => $config['type'],
                'host' => $config['hostname'],
                'name' => $config['database'],
                'user' => $config['username'],
                'pass' => $config['password'],
                'port' => $config['hostport'],
                'charset' => $config['charset'],
                'table_prefix' => $config['prefix'],
            ];
        } else {
            $dbConfig = [
                'adapter' => explode(',', $config['type'])[0],
                'host' => explode(',', $config['hostname'])[0],
                'name' => explode(',', $config['database'])[0],
                'user' => explode(',', $config['username'])[0],
                'pass' => explode(',', $config['password'])[0],
                'port' => explode(',', $config['hostport'])[0],
                'charset' => explode(',', $config['charset'])[0],
                'table_prefix' => explode(',', $config['prefix'])[0],
            ];
        }

        return $dbConfig;
    }

    protected function verifyMigrationDirectory($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('Migration directory "%s" does not exist', $path));
        }

        if (!is_writable($path)) {
            throw new \InvalidArgumentException(sprintf('Migration directory "%s" is not writable', $path));
        }
    }

    protected function executeMigration(MigrationInterface $migration, $pluginVersion, $direction = MigrationInterface::UP)
    {
        $startTime = time();
        $direction = ($direction === MigrationInterface::UP) ? MigrationInterface::UP : MigrationInterface::DOWN;
        $migration->setAdapter($this->getAdapter());

        // begin the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->beginTransaction();
        }

        // Run the migration
        if (method_exists($migration, MigrationInterface::CHANGE)) {
            if ($direction === MigrationInterface::DOWN) {
                // Create an instance of the ProxyAdapter so we can record all
                // of the migration commands for reverse playback
                /** @var ProxyAdapter $proxyAdapter */
                $proxyAdapter = AdapterFactory::instance()->getWrapper('proxy', $this->getAdapter());
                $migration->setAdapter($proxyAdapter);
                /** @noinspection PhpUndefinedMethodInspection */
                $migration->change();
                $proxyAdapter->executeInvertedCommands();
                $migration->setAdapter($this->getAdapter());
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                $migration->change();
            }
        } else {
            $migration->{$direction}();
        }

        // commit the transaction if the adapter supports it
        if ($this->getAdapter()->hasTransactions()) {
            $this->getAdapter()->commitTransaction();
        }

        $endTime = time();
        return false !== PluginMigrations::saveMigration($this->pluginElement, $migration, $pluginVersion, $direction, $startTime, $endTime);
    }

    /**
     * @param string|null 限制某个插件版本
     * @return array 获取插件的所有版本信息
     */
    public function getVersions($targetVersion = null)
    {
        return PluginMigrations::getVersions($this->pluginElement, $targetVersion);
    }

    public function getMigrations()
    {
        if (null === $this->migrations) {
            $migrations = $this->pluginElement->getElement('migrations', []);

            // filter the files to only get the ones that match our naming scheme
            $file_names = [];
            /** @var Migrator[] $versions */
            $versions = [];
            $base_path = $this->pluginElement->getRootPath();
            foreach ($migrations as $plugin_version => $plugin_migrations) {
                if (!isset($file_names[$plugin_version])) $file_names[$plugin_version] = [];
                foreach ($plugin_migrations as $relative_path) {
                    $file_path = $base_path . $relative_path;
                    if (Util::isValidMigrationFileName(basename($file_path))) {
                        $version = Util::getVersionFromFileName(basename($file_path));

                        if (isset($versions[$plugin_version][$version])) {
                            throw new \InvalidArgumentException(sprintf('Duplicate migration - "%s" has the same version as "%s"', $file_path, $versions[$version]->getVersion()));
                        }

                        // convert the filename to a class name
                        $class = Util::mapFileNameToClassName(basename($file_path));

                        if (isset($file_names[$plugin_version][$class])) {
                            throw new \InvalidArgumentException(sprintf('Migration "%s" has the same name as "%s"', basename($file_path), $file_names[$plugin_version][$class]));
                        }

                        $file_names[$plugin_version][$class] = basename($file_path);

                        // load the migration file
                        /** @noinspection PhpIncludeInspection */
                        require_once $file_path;
                        if (!class_exists($class)) {
                            throw new \InvalidArgumentException(sprintf('Could not find class "%s" in file "%s"', $class, $file_path));
                        }

                        // instantiate it
                        $migration = new $class($version);

                        if (!($migration instanceof AbstractMigration)) {
                            throw new \InvalidArgumentException(sprintf('The class "%s" in file "%s" must extend \Phinx\Migration\AbstractMigration', $class, $file_path));
                        }

                        $versions[$plugin_version][$version] = $migration;
                    }
                }
            }

            $this->sortItems($versions);
            $this->migrations = $versions;
        }

        return $this->migrations;
    }

    protected function sortItems($items, $sort = SORT_ASC)
    {
        array_map(function ($item) use ($sort) {
            if ($sort === SORT_ASC) ksort($item);
            else krsort($item);
            return $item;
        }, $items);
        if ($sort === SORT_ASC) ksort($items);
        else krsort($items);
    }

    public function handleMigration($migrations, $version, $currentVersion, array $versions = [], $compare = '>=')
    {
        if(empty($versions)) $versions = $this->getVersions();
        if (empty($versions) && empty($migrations)) {
            return;
        }

        if (null === $version) {
            $version = $this->pluginElement->getVersion();
        } else {
            if (0 != $version && !isset($migrations[$version])) {
                //这里应该抛出错误信息
                throw new InvalidArgumentException(sprintf('warning: %s is not valid version', $version));
            }
        }
        //这里判断版本号是否正确
        //dump($versions);exit;

        // are we migrating up or down?
        if (is_array($currentVersion)) {
            $direction = version_compare($version ,$currentVersion['plugin_version'], $compare) ? MigrationInterface::UP : MigrationInterface::DOWN;
        } else {
            $direction = version_compare($version ,$currentVersion, $compare) ? MigrationInterface::UP : MigrationInterface::DOWN;
        }

        $executed = [];
        if ($direction === MigrationInterface::DOWN) {
            // run downs first
            $this->sortItems($migrations, SORT_DESC);
            foreach ($migrations as $plugin_version => $plugin_migrations) {
                if ($version !== 0 && $plugin_version < $version) {
                    break;
                }
                foreach ($plugin_migrations as $migration) {
                    if ($version === 0 || in_array($migration->getVersion(), $versions[$version])) {
                        $result = $this->executeMigration($migration, $plugin_version, MigrationInterface::DOWN);
                        if($result) {
                            if(!isset($executed[$plugin_version])) {
                                $executed[$plugin_version] = [
                                    MigrationInterface::UP => [],
                                    MigrationInterface::DOWN=> [],
                                ];
                            }
                            $executed[$plugin_version][MigrationInterface::DOWN][] = $migration->getVersion();
                        }
                    }
                }
            }
        }

        $this->sortItems($migrations);
        foreach ($migrations as $plugin_version => $plugin_migrations) {
            if ($plugin_version > $version) {
                break;
            }

            foreach ($plugin_migrations as $migration) {
                if (!isset($versions[$version]) || !in_array($migration->getVersion(), $versions[$version])) {
                    $result = $this->executeMigration($migration, $plugin_version, MigrationInterface::UP);
                    if($result) {
                        if(!isset($executed[$plugin_version])) {
                            $executed[$plugin_version] = [
                                MigrationInterface::UP => [],
                                MigrationInterface::DOWN=> [],
                            ];
                        }
                        $executed[$plugin_version][MigrationInterface::UP][] = $migration->getVersion();
                    }
                }
            }
        }
        return $executed;
    }

    protected function migrate($version = null, $force = false)
    {
        $migrations = $this->getMigrations();
        $versions = $this->getVersions();
        $current = $this->getCurrentVersion();
        $this->handleMigration($migrations, $version, $current, $versions);

    }

    protected function getCurrentVersion()
    {
        $versions = $this->getVersions();
        $version = 0;

        if (!empty($versions)) {
            foreach (array_reverse($versions, true) as $key => $versions) {
                return ['plugin_version' => $key, 'migration_versions' => $versions];
            }
        }
        return $version;
    }

    protected function migrateUpgrade($version = null, $force = false)
    {
        $migrations = $this->getMigrations();
        $versions = $this->getVersions();
        $current = $this->getCurrentVersion();

        if (empty($versions) && empty($migrations)) {
            return;
        }

        if (null === $version) {
            $version = $this->pluginElement->getVersion();
        } else {
            if (0 != $version && !isset($migrations[$version])) {
                //这里应该抛出错误信息
                throw new InvalidArgumentException(sprintf('warning: %s is not valid version', $version));
            }
        }
        //这里判断版本号是否正确
        //dump($versions);exit;

        // are we migrating up or down?
        if (is_array($current)) {
            $direction = $version > $current['plugin_version'] ? MigrationInterface::UP : MigrationInterface::DOWN;
            $current_version = $current['plugin_version'];
        } else {
            $direction = $version > $current ? MigrationInterface::UP : MigrationInterface::DOWN;
            $current_version = $current;
        }

        if ($direction === MigrationInterface::DOWN) {
            // run downs first
            $this->sortItems($migrations, SORT_DESC);
            foreach ($migrations as $plugin_version => $plugin_migrations) {
                if ($version !== 0 && $plugin_version <= $version) {
                    break;
                }
                foreach ($plugin_migrations as $migration) {
                    if ($version === 0 || in_array($migration->getVersion(), $versions[$version])) {
                        $this->executeMigration($migration, MigrationInterface::DOWN);
                    }
                }
            }
        }

        $this->sortItems($migrations);
        foreach ($migrations as $plugin_version => $plugin_migrations) {
            if ($plugin_version > $version) {
                break;
            }
            if ($plugin_version > $current_version) {
                foreach ($plugin_migrations as $migration) {
                    if (!isset($versions[$version]) || !in_array($migration->getVersion(), $versions[$version])) {
                        $this->executeMigration($migration, MigrationInterface::UP);
                    }
                }
            }
        }
    }
}