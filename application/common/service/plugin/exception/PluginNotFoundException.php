<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 * (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Created by PhpStorm.
 * User: johnzhang
 * Date: 2019/7/25 0025
 * Time: 上午 10:56
 */

namespace app\common\service\plugin\exception;


use Throwable;

class PluginNotFoundException extends \LogicException
{
    protected $missingPluginCode;
    public function __construct($missingCode, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->missingPluginCode = $missingCode;
        parent::__construct($message, $code, $previous);
    }
}