<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */
use think\Log;
use think\Request;

// 应用公共文件

function write_log($txt)
{
    \think\App::$debug && Log::record($txt);
}

/**
 * 打印调试函数
 * @param $content
 * @param $is_die
 */
function pre($content, $is_die = true)
{
    header('Content-type: text/html; charset=utf-8');
    echo '<pre>' . print_r($content, true);
    $is_die && die();
}

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

/**
 * 写入日志
 * @param string|array $values
 * @param string $dir
 * @return bool|int
 */
function write_log2($values, $dir)
{
    if (is_array($values))
        $values = print_r($values, true);
    // 日志内容
    $content = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL . $values . PHP_EOL . PHP_EOL;
    try {
        // 文件路径
        $filePath = $dir . '/logs/';
        // 路径不存在则创建
        !is_dir($filePath) && mkdir($filePath, 0755, true);
        // 写入文件
        return file_put_contents($filePath . date('Ymd') . '.log', $content, FILE_APPEND);
    } catch (\Exception $e) {
        return false;
    }
}

function merge_log($value, $type, $title_log = false)
{
    // 日志内容
    if ($title_log) {
        $content = PHP_EOL . '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL . $value . PHP_EOL;
    } else {
        $content = $value . PHP_EOL;
    }
    try {
        // 文件路径
        $filePath = RUNTIME_PATH . '/log/merge_log/';
        // 路径不存在则创建
        !is_dir($filePath) && mkdir($filePath, 0755, true);
        // 写入文件
        return file_put_contents($filePath . $type . '.log', $content, FILE_APPEND);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 写入日志 (使用tp自带驱动记录到runtime目录中)
 * @param $value
 * @param string $type
 * @return bool
 */
function log_write($value, $type = 'yoshop-info')
{
    $msg = is_string($value) ? $value : print_r($value, true);
    return Log::write($msg, $type);
}

/**
 * curl请求指定url (get)
 * @param $url
 * @param array $data
 * @return mixed
 */
function curl($url, $data = [])
{
    // 处理get数据
    if (!empty($data)) {
        $url = $url . '?' . http_build_query($data);
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

/**
 * curl请求指定url (post)
 * @param $url
 * @param array $data
 * @return mixed
 */
function curlPost($url, $data = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
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

if (!function_exists('wx_app')) {
    function wx_app($appType = 'officialAccount', $config = [], $cacheOption = [])
    {
        if (empty($config)) {
            $configWrapper = config("wxapp.{$appType}");
            if ($configWrapper) {
                $config = $configWrapper['config'];
            } else {
                $config = [];
            }
        }
        if (empty($cacheOption)) {
            $configWrapper = config("wxapp.{$appType}");
            if ($configWrapper) {
                $cacheOption = $configWrapper['cache'];
            } else {
                $cacheOption = [];
            }
        }
        /** @var \EasyWeChat\Kernel\ServiceContainer $application */
        //$application = \EasyWeChat\Factory::{$appType}($config);
        $application = call_user_func("\EasyWeChat\Factory::{$appType}", $config);
        $cacheShort = ucfirst($cacheOption['type']);
        $cacheType = $cacheShort . 'Cache';
        $className = "Symfony\\Component\\Cache\\Simple\\{$cacheType}";
        if ($cacheShort == 'Redis') {
            if (extension_loaded('redis')) {
                $redis = new \Redis();
                $redis->connect($cacheOption['options']['host'], $cacheOption['options']['port']);
                if (!empty($cacheOption['options']['password'])) {
                    $redis->auth($cacheOption['options']['password']);
                }
                $redis->select(isset($cacheOption['options']['db']) ? $cacheOption['options']['db'] : 0);
                $application->rebind('cache', new $className($redis));
                return $application;
            }
        }
        return $application;
    }
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
if (!function_exists('seconds2duration')) {
    /**
     * 计算持续时长
     *
     * @param int $seconds 秒数
     * @return string $duration 5天10小时43分钟40秒
     */
    function second2duration($seconds, $ignoreSeconds = true)
    {
        $duration = '';

        $seconds = (int)$seconds;
        if ($seconds <= 0) {
            return $duration;
        }

        list($day, $hour, $minute, $second) = explode(' ', gmstrftime('%j %H %M %S', $seconds));

        $day -= 1;
        if ($day > 0) {
            $duration .= (int)$day . '天';
        }
        if ($hour > 0) {
            $duration .= (int)$hour . '小时';
        }
        if ($minute > 0) {
            $duration .= (int)$minute . '分钟';
        }
        if ($second > 0 && !$ignoreSeconds) {
            $duration .= (int)$second . '秒';
        }

        return $duration;
    }
}
if (!function_exists('msectime')) {
    function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}
if (!function_exists('save_log')) {
    function save_log($key, $content, $v1 = '', $v2 = '')
    {
        $record = new \app\common\model\Log();
        return false !== $record->save(compact('content', 'v1', 'v2', 'key'));
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
