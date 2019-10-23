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
 * Date: 2019/7/30 0030
 * Time: 下午 4:29
 */

namespace app\common\service\plugin\asset;


use app\common\service\plugin\PluginElement;
use app\common\service\plugin\PluginManager;
use EasyWeChat\Kernel\Support\Arr;

class AssetManager
{
    protected $manager;
    protected $element;

    public function __construct(PluginManager $manager)
    {
        $this->manager = $manager;
    }

    public function uninstall(PluginElement $element, $force = false)
    {
        $this->element = $element;
        $json = $this->buildJsonData();
        $idx = false;
        foreach ($json as $index => $plugin_asset) {
            if (Arr::get($plugin_asset, 'code') == $this->element->getCode()) {
                $idx = $index;
                break;
            }
        }
        if ($idx !== false) unset($json[$idx]);
        return $this->saveJsonData($json, $force);
    }
    public function upgrade(PluginElement $element, $force = false)
    {
        $this->element = $element;
        $json = $this->buildJsonData();
        $idx = false;
        foreach ($json as $index => $plugin_asset) {
            if (Arr::get($plugin_asset, 'code') == $element->getCode()) {
                $idx = $index;
                break;
            }
        }
        if ($idx === false) $json[] = $element->getAssetConfig();
        else $json[$idx] = $element->getAssetConfig();
        return $this->saveJsonData($json, $force);
    }
    public function install(PluginElement $element, $force = false)
    {
        $this->element = $element;
        $json = $this->buildJsonData();
        $idx = false;
        foreach ($json as $index => $plugin_asset) {
            if (Arr::get($plugin_asset, 'code') == $element->getCode()) {
                $idx = $index;
                break;
            }
        }
        if ($idx === false) $json[] = $element->getAssetConfig();
        else $json[$idx] = $element->getAssetConfig();
        return $this->saveJsonData($json, $force);
    }

    protected function buildJsonData()
    {
        $path = CACHE_PATH . 'plugin_asset.json';
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true);
    }

    protected function saveJsonData($json, $force)
    {
        $path = CACHE_PATH . 'plugin_asset.json';
        return false !== file_put_contents($path, json_encode(array_values(array_filter($json))));
    }

    public function rebuild()
    {
        $validPlugins = $this->manager->getPlugins();
        $json = [];
        foreach ($validPlugins as $validPlugin) {
            $json[] = $validPlugin->getAssetConfig();
        }
        return $this->saveJsonData($json, true);
    }
}