<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

use app\common\service\lock\LockFactoryInterface;
use think\Request;

/**
 * 驼峰命名转下划线命名
 * @param $str
 * @return string
 */
function toUnderScore($str)
{
    $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
        return '_' . strtolower($matchs[0]);
    }, $str);
    return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
}

/**
 * 生成密码hash值
 * @param $password
 * @return string
 */
function mp_hash($password)
{
    return md5(md5($password) . config('backend.salt')); //'yoshop_salt_SmTRx');
}

/**
 * 获取当前域名及根路径
 * @return string
 */
function base_url()
{
    $request = Request::instance();
    $subDir = str_replace('\\', '/', dirname($request->server('PHP_SELF')));
    return $request->scheme() . '://' . $request->host() . $subDir . ($subDir === '/' ? '' : '/');
}

if (!function_exists('array_column')) {
    /**
     * array_column 兼容低版本php
     * (PHP < 5.5.0)
     * @param $array
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    function array_column($array, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($array as $subArray) {
            if (is_null($indexKey) && array_key_exists($columnKey, $subArray)) {
                $result[] = is_object($subArray) ? $subArray->$columnKey : $subArray[$columnKey];
            } elseif (array_key_exists($indexKey, $subArray)) {
                if (is_null($columnKey)) {
                    $index = is_object($subArray) ? $subArray->$indexKey : $subArray[$indexKey];
                    $result[$index] = $subArray;
                } elseif (array_key_exists($columnKey, $subArray)) {
                    $index = is_object($subArray) ? $subArray->$indexKey : $subArray[$indexKey];
                    $result[$index] = is_object($subArray) ? $subArray->$columnKey : $subArray[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 多维数组合并
 * @param $array1
 * @param $array2
 * @return array
 */
function array_merge_multiple($array1, $array2)
{
    $merge = $array1 + $array2;
    $data = [];
    foreach ($merge as $key => $val) {
        if (
            isset($array1[$key])
            && is_array($array1[$key])
            && isset($array2[$key])
            && is_array($array2[$key])
        ) {
            $data[$key] = array_merge_multiple($array1[$key], $array2[$key]);
        } else {
            $data[$key] = isset($array2[$key]) ? $array2[$key] : $array1[$key];
        }
    }
    return $data;
}

/**
 * 二维数组排序
 * @param $arr
 * @param $keys
 * @param bool $desc
 * @return mixed
 */
function array_sort($arr, $keys, $desc = false)
{
    $key_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $key_value[$k] = $v[$keys];
    }
    if ($desc) {
        arsort($key_value);
    } else {
        asort($key_value);
    }
    reset($key_value);
    foreach ($key_value as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}

/**
 * 数据导出到excel(csv文件)
 * @param $fileName
 * @param array $tileArray
 * @param array $dataArray
 */
function export_excel($fileName, $tileArray = [], $dataArray = [])
{
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 0);
    ob_end_clean();
    ob_start();
    header("Content-Type: text/csv");
    header("Content-Disposition:filename=" . $fileName);
    $fp = fopen('php://output', 'w');
    fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));// 转码 防止乱码(比如微信昵称)
    fputcsv($fp, $tileArray);
    $index = 0;
    foreach ($dataArray as $item) {
        if ($index == 1000) {
            $index = 0;
            ob_flush();
            flush();
        }
        $index++;
        fputcsv($fp, $item);
    }
    ob_flush();
    flush();
    ob_end_clean();
}

/**
 * 获取当前系统版本号
 * @return mixed|null
 * @throws Exception
 */
function get_version()
{
    static $version = null;
    if ($version) {
        return $version;
    }
    $file = ROOT_PATH . '/version.json';
    if (!file_exists($file)) {
        throw new Exception('version.json not found');
    }
    $version = json_decode(file_get_contents($file), true);
    if (!is_array($version)) {
        throw new Exception('version cannot be decoded');
    }
    return $version['version'];
}


if (!function_exists('to_cli_url')) {
    function to_cli_url($url = '', $vars = '', $suffix = true, $domain = false)
    {
        if (false === $domain) $domain = \think\Env::get('cli_domain', '');
        return url($url, $vars, $suffix, $domain);
    }
}
if (!function_exists('app')) {
    /**
     * @param $id
     * @param bool $ignoreNotFound
     * @return mixed
     */
    function app($id, $ignoreNotFound = false)
    {
        static $container, $inner;
        if (!isset($container)) {
            $inner = new \app\common\service\ServiceContainer();
            $container = new \Pimple\Psr11\Container($inner);
        }
        if ($id === true) return $inner;
        if (empty($id) || !is_string($id)) {
            return $container;
        }
        if ($ignoreNotFound) {
            return $container->has($id) ? $container->get($id) : null;
        }
        return $container->get($id);
    }
}

if (!function_exists('is_absolute_path')) {
    /**
     * Returns whether the file path is an absolute path.
     * @param string $path A file path
     * @return bool
     */
    function is_absolute_path($path)
    {
        return strspn($path, '/\\', 0, 1)
            || (\strlen($path) > 3 && ctype_alpha($path[0])
                && ':' === $path[1]
                && strspn($path, '/\\', 2, 1)
            )
            || null !== parse_url($path, PHP_URL_SCHEME);
    }
}
if (!function_exists('config_get')) {
    function config_get($name, $default = null, $range = '') {
        $index = strpos($name, '.');
        if($index > 0) {
            $config = (array)\think\Config::get(substr($name, 0, $index), $range);
            return \EasyWeChat\Kernel\Support\Arr::get($config, substr($name, $index+1), $default);
        }
        $config = \think\Config::get($name, $range);
        return is_null($config) ? $default : $config;
    }
}
if(!function_exists('scope_locker')) {
    /**
     * @param $scope
     * @param $bin
     * @param $resource
     * @return \Symfony\Component\Lock\LockInterface
     */
    function scope_locker($scope, $bin, $resource) {
        static $locks = [];
        if(!isset($locks[$scope]) || is_null($locks[$scope])) {
            /** @var LockFactoryInterface $factory */
            $factory = app('lock.factory');
            $locker = $factory->get($bin);
            $locks[$scope] = $locker->createLock($resource);
        }
        return $locks[$scope];
    }
}
