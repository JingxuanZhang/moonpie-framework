<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/20
 * Time: 11:53
 */

namespace app\common\service\log;

use app\common\service\ServiceContainer;
use EasyWeChat\Kernel\Log\LogManager as BaseManager;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

class LogManager extends BaseManager
{
    /**
     * @var ServiceContainer
     */
    protected $app;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }

    /**
     * 创建一个紧急日志处理器避免死亡白屏
     * @return \Monolog\Logger
     * @throws \Exception
     */
    protected function createEmergencyLogger()
    {
        return new Monolog('Moonpie', $this->prepareHandlers([new StreamHandler(
            \sys_get_temp_dir() . '/moonpie/moonpie.log', $this->level(['level' => 'debug'])
        )]));
    }
}