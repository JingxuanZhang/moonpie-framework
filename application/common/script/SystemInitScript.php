<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/6/18
 * Time: 17:57
 */

namespace app\common\script;

use Composer\Script\Event;
use Composer\Util\ProcessExecutor;

class SystemInitScript
{
    public static function onInstall(Event $event)
    {
        $base_path = getcwd() . DIRECTORY_SEPARATOR;
        $target = $base_path . '.env';
        if (!file_exists($target)) {
            /** @var \Composer\IO\IOInterface $io */
            $io = $event->getIO();
            $env = $io->ask('what is your current environment?[default: local]', 'local');
            //$io->write('your environment is ' . $env . ' and current path: ' . getcwd());
            $source = $base_path . '.env.example';
            $config = parse_ini_file($source);
            $config['app_status'] = $env;
            static::iniFile($target, $config);
            $io->write("copy {$source} to {$target} successfully\n");
            //接下来是创建环境配置目录
            $config_file = $base_path . 'application' . DIRECTORY_SEPARATOR . $env . '.php';
            if (!file_exists($config_file)) {
                $continue = $io->askConfirmation('would you want to create the environment config file ' . $config_file . ' now ?');
                if($continue) {
                    $default_config = [
                        'backend' => [
                            'session_name' => 'input your admin session here',
                            'salt' => 'use your self crypt salt.',
                        ],
                        'database' => [
                            // 数据库类型
                            'type' => 'mysql',
                            // 服务器地址
//    'hostname' => '',
                            'hostname' => 'localhost',
                            // 数据库名
                            'database' => 'test',
                            // 用户名
                            'username' => 'root',
                            // 密码
                            'password' => 'root',
                            // 端口
                            'hostport' => '',
                            // 连接dsn
                            'dsn' => '',
                            // 数据库连接参数
                            'params' => [],
                            // 数据库编码默认采用utf8
                            'charset' => 'utf8',
                            // 数据库表前缀
                            'prefix' => '',
                            // 数据库调试模式
                            'debug' => false,
                            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
                            'deploy' => 0,
                            // 数据库读写是否分离 主从式有效
                            'rw_separate' => false,
                            // 读写分离后 主服务器数量
                            'master_num' => 1,
                            // 指定从服务器序号
                            'slave_no' => '',
                            // 自动读取主库数据
                            'read_master' => false,
                            // 是否严格检查字段是否存在
                            'fields_strict' => true,
                            // 数据集返回类型
                            'resultset_type' => 'array',
                            // 自动写入时间戳字段
                            'auto_timestamp' => false,
                            // 时间字段取出后的默认时间格式
                            'datetime_format' => 'Y-m-d H:i:s',
                            // 是否需要进行SQL性能分析
                            'sql_explain' => false,
                        ]
                    ];
                    $size = file_put_contents($config_file, "<?php \r\n return " . static::varExport($default_config, true)
                    . ";\r\n");
                    if($size) {
                        $io->write('configuration create successfully. ');
                        $continue = $io->askConfirmation('would you initialize your system next? notice: please ensure your database is connected successfully');
                        if($continue) {
                            $executor = new ProcessExecutor($io);
                            $exit_code = $executor->execute("php -f think migrate:run", $output, $base_path);
                            if($exit_code === 0) {
                                $io->write('migration successfully, next you can:');
                                $io->write('1. run `php think app:backend-init` to init system admin');
                                $io->write('2. run `yarn install && yarn encore dev` to prepare frontend resources automatically');
                            }else {
                                $io->writeError('database migration failed, please ensure your database connection is ready');
                            }
                        }
                    }
                }
            }
        }
    }

    protected static function varExport($expression, $return = true)
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        if ((bool)$return) return $export; else echo $export;
    }

    public static function iniFile($inifilename, $initdata = [], $key = null, $value = null, $mode = null)
    {
        $alter_all = false;
        if (!file_exists($inifilename)) {
            $confarr = $initdata;
            $alter_all = true;
        } else if (!empty($initdata)) {
            $confarr = $initdata;
            $alter_all = true;
        } else {
            //读取
            $confarr = parse_ini_file($inifilename, true);
        }
        $newini = "";
        if (!$alter_all) {
            $key = $key == null ? 'user' : $key;
            if ($mode != null) {
                //节名不为空
                if ($value == null) {
                    return $confarr[$mode][$key] == null ? null : $confarr[$mode][$key];
                } else {
                    $YNedit = $confarr[$mode][$key] == $value ? false : true;//若传入的值和原来的一样，则不更改
                    $confarr[$mode][$key] = $value;
                }
            } else {//节名为空

                if ($value == null) {
                    return $confarr[$key] == null ? null : $confarr[$key];
                } else {
                    $YNedit = $confarr[$key] == $value ? false : true;//若传入的值和原来的一样，则不更改
                    $newini = $newini . $key . "=" . $value . "\r\n";
                }

            }
            if (!$YNedit) return true;
        }
        //更改
        $Mname = array_keys($confarr);
        $jshu = 0;
        foreach ($confarr as $k => $v) {
            if (!is_array($v)) {
                $newini = $newini . $Mname[$jshu] . "=" . $v . "\r\n";
                $jshu += 1;
            } else {
                $newini = $newini . '[' . $Mname[$jshu] . "]\r\n";//节名
                $jshu += 1;
                $jieM = array_keys($v);
                $jieS = 0;
                foreach ($v as $k2 => $v2) {
                    $newini = $newini . $jieM[$jieS] . "=" . $v2 . "\r\n";
                    $jieS += 1;
                }
            }

        }
        if (($fi = fopen($inifilename, "w"))) {
            flock($fi, LOCK_EX);//排它锁
            fwrite($fi, $newini);
            flock($fi, LOCK_UN);
            fclose($fi);
            return true;
        }
        return false;//写文件失败
    }
}