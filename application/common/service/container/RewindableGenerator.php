<?php
/**
 * Copyright (c) 2018-2019.
 * This file is part of the moonpie production
 *  (c) johnzhang <875010341@qq.com>
 * This source file is subject to the MIT license that is bundled
 *  with this source code in the file LICENSE.
 */

namespace app\common\service\container;


use Countable;
use IteratorAggregate;
class RewindableGenerator implements Countable, IteratorAggregate
{
    /**
     * The generator callback.
     *
     * @var callable
     */
    protected $generator;
    /**
     * The number of tagged services.
     *
     * @var callable|int
     */
    protected $count;
    /**
     * Create a new generator instance.
     *
     * @param  callable  $generator
     * @param  callable|int  $count
     * @return void
     */
    public function __construct(callable $generator, $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }
    /**
     * Get an iterator from the generator.
     *
     * @return mixed
     */
    public function getIterator()
    {
        return ($this->generator)();
    }
    /**
     * Get the total number of tagged services.
     *
     * @return int
     */
    public function count()
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }
        return $this->count;
    }
}