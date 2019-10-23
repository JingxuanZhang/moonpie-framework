<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/10
 * Time: 11:55
 */

namespace app\common\behavior;


use app\common\exception\MaintenanceModeException;

class MaintenanceMode
{
    public function run(&$params)
    {
        $path = RUNTIME_PATH . 'framework/down';
        if (file_exists($path)) {
            $vars = json_decode(file_get_contents($path), true);
            throw new MaintenanceModeException($vars['time'], $vars['retry'], $vars['message']);
        }
    }
}