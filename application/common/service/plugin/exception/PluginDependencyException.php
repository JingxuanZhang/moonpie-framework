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
 * Time: 上午 10:58
 */

namespace app\common\service\plugin\exception;


use app\common\service\plugin\PluginElement;
use Throwable;

class PluginDependencyException extends \LogicException
{
    protected $element;
    protected $dependency;
    public function __construct(PluginElement $element, array $dependency, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->element = $element;
        $this->dependency = $dependency;
        parent::__construct($message, $code, $previous);
    }

}