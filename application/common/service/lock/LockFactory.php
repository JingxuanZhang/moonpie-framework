<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/1
 * Time: 8:55
 */

namespace app\common\service\lock;


use app\common\service\traits\ServiceAwareTrait;
use EasyWeChat\Kernel\Support\Arr;
use think\Config;

class LockFactory implements LockFactoryInterface
{
    use ServiceAwareTrait;
    protected $backendBins;

    public function __construct(array $backendBins = [])
    {
        $this->backendBins = $backendBins;
    }

    public function get($bin)
    {
        $container = $this->getServiceContainer();
        $bins = Arr::get($container->config, 'lock', []);
        if (isset($bins[$bin]) && !empty($bins[$bin])) {//优先使用系统配置的管理配置
            $serviceName = $bins[$bin];
        } else if (isset($this->backendBins[$bin]) && !empty($this->backendBins[$bin])) {
            $serviceName = $this->backendBins[$bin];
        } else {
            //默认使用
            $serviceName = 'lock.factory.default';
        }
        return $this->getServiceContainer()->offsetGet($serviceName);
    }

}