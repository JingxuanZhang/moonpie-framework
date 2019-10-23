<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/6/3
 * Time: 9:05
 */

namespace app\manager\validate;


use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'username' => 'require|alphaDash|length:2,100',
        'password' => 'require|alphaDash|length:6,20|confirm',
    ];
}