<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/1
 * Time: 8:53
 */

namespace app\common\service\lock;


use Symfony\Component\Lock\Factory;

interface LockFactoryInterface
{
    /**
     * @param string $bin lock服务名
     * @return Factory
     */
    public function get($bin);
}