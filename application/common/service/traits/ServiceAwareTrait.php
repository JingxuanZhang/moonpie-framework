<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/1
 * Time: 9:01
 */

namespace app\common\service\traits;


use Psr\Container\ContainerInterface;

trait ServiceAwareTrait
{
    protected $serviceContainer;
    public function setServiceContainer(ContainerInterface $container)
    {
        $this->serviceContainer = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }
}