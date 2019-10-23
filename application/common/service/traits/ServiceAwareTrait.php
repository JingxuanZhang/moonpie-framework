<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/1
 * Time: 9:01
 */

namespace app\common\service\traits;

use Pimple\Container;

trait ServiceAwareTrait
{
    protected $serviceContainer;
    public function setServiceContainer(Container $container)
    {
        $this->serviceContainer = $container;
    }

    /**
     * @return Container
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }
}