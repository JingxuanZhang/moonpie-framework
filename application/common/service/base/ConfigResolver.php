<?php


namespace app\common\service\base;


use EasyWeChat\Kernel\Support\Arr;
use think\Config;

class ConfigResolver
{
    /**
     * 根据插件的配置合并策略，合并配置信息
     * @param array $params
     */
    public function mergeConfigs(array $params)
    {
        foreach($params as $config_set) {
            if(Arr::has($config_set, ['range_name', 'real_path', 'strategy', 'plugin_element', 'config_name'])) {
                switch ($config_set['strategy']) {
                    case 'merge'://这里需要合并配置，但必须确保开发者能够覆盖插件的配置
                        $exists = (array) Config::get($config_set['config_name'], $config_set['range_name']);
                        $base = include $config_set['real_path'];
                        $configs = $exists + $base;
                        Config::set($configs, null, $config_set['range_name']);
                        break;
                    case 'outside'://使用插件的配置
                        Config::load($config_set['real_path'], $config_set['config_name'], $config_set['range_name']);
                        break;
                    case 'override':
                    default:
                        continue;
                        break;
                }
            }
        }
    }
}