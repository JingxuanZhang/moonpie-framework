<?php
/**
 * Copyright (c) 2018-2019.
 *  This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 *  This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\resolver;



use Psr\Container\ContainerInterface;

class ClassResolver implements ClassResolverInterface
{
    protected $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createFromDefinition($flag, $definition = [], $returnId = false)
    {
        if (is_string($flag)) {
            try {
                $reflector = new \ReflectionClass($flag);
                $constructor = $reflector->getConstructor();
                $require_args_num = $constructor->getNumberOfRequiredParameters();
                if($require_args_num > 0) {
                    $class = $reflector->newInstanceArgs($definition);
                }else {
                    $class = $reflector->newInstance();
                }
                $id = $reflector->getName();
            } catch (\ReflectionException $e) {
                if (!$this->container->has($flag)) {
                    throw new \InvalidArgumentException(sprintf('class flag "%s" is not exists in our service container', $flag));
                }
                $id = $flag;
                $class = $this->container->get($flag);
            }
        } else if(is_array($flag)){
            //处理数组部分
            //首先处理包含类的数据
            if(isset($flag['class'])) {
                $class_name = $flag['class'];
                unset($flag['class']);
                return $this->createFromDefinition($class_name, array_merge($flag, $definition), $returnId);
            } else if(is_callable($flag, true)){
                $id = null;
                $class = call_user_func_array($flag, $definition);
            }
        }else if (is_object($flag)) {
            $class = $flag;
            $id = spl_object_hash($flag);
        } else {
            throw new \InvalidArgumentException(sprintf('class flag "%s" has unsupported format "%s"', $flag,
                gettype($flag)
            ));
        }
        return $returnId ? [$id, $class] : $class;
    }
}