<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

/**
 * Created by PhpStorm.
 * User: johnzhang
 * Date: 2019/7/27 0027
 * Time: 下午 4:52
 */

namespace app\common\model;


use app\common\service\plugin\PluginElement;
use Phinx\Migration\MigrationInterface;

class PluginMigrations extends BaseModel
{
    /**
     * 获取插件所有/指定版本的执行版本信息
     * @param PluginElement $element
     * @param null $version
     * @return array
     */
    public static function getVersions(PluginElement $element, $version = null)
    {
        $query = static::where('plugin_code', $element->getCode());
        if (!empty($version)) $query->where('plugin_version', $version);
        $query->order('plugin_code', 'ASC')
            ->order('migration_version', 'ASC');
        $query->field(['migration_version', 'plugin_version']);
        $results = $query->select();
        if(empty($results)) return [];
        $return = [];
        foreach($results as $result) {
            if(!isset($return[$result['plugin_version']])){
                $return[$result['plugin_version']] = [];
            }
            if(!in_array($result['migration_version'], $return[$result['plugin_version']])){
                $return[$result['plugin_version']][] = $result['migration_version'];
            }
        }

        return $return;
    }

    public static function saveMigration(PluginElement $element, MigrationInterface $migration, $plugin_version, $direction, $start, $end)
    {
        $has_one = static::where('plugin_code', $element->getCode())
                ->where('migration_version', $migration->getVersion())->count() > 0;
        if(strcasecmp($direction, MigrationInterface::UP) === 0) {
            //这里执行的是上行
            if (!$has_one) {
                // up
                $record = new static();
                $save = [
                    'plugin_code' => $element->getCode(),
                    'plugin_version' => $plugin_version,
                    'migration_version' => $migration->getVersion(),
                    'migration_name' => substr($migration->getName(), 0, 100),
                    'start_time' => date('Y-m-d H:i:s', $start),
                    'end_time' => date('Y-m-d H:i:s', $end),
                    'breakpoint' => 0,
                ];
                return false !== $record->save($save);
            } else {
                return true;
            }
        }else {
            //这里的是下行
            return false !== static::where('plugin_code', $element->getCode())
                ->where('migration_version', $migration->getVersion())
                ->delete();
        }
    }
}