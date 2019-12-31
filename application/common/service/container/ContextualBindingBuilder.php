<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\container;


use app\common\service\ServiceContainer;

class ContextualBindingBuilder
{
    /**
     * The underlying container instance.
     *
     * @var ServiceContainer
     */
    protected $container;
    /**
     * The concrete instance.
     *
     * @var string|array
     */
    protected $concrete;
    /**
     * The abstract target.
     *
     * @var string
     */
    protected $needs;
    /**
     * Create a new contextual binding builder.
     *
     * @param  ServiceContainer  $container
     * @param  string|array  $concrete
     * @return void
     */
    public function __construct(ServiceContainer $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }
    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string  $abstract
     * @return $this
     */
    public function needs($abstract)
    {
        $this->needs = $abstract;
        return $this;
    }
    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string  $implementation
     * @return void
     */
    public function give($implementation)
    {
        foreach (Util::arrayWrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }
}