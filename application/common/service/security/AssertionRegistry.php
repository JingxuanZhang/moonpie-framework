<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/26
 * Time: 14:55
 */

namespace app\common\service\security;

use EasyWeChat\Kernel\Support\Collection;
use Pimple\Container;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;

/**
 * 此类用来管理项目中所有可以使用的权限资源验证类，或其服务标识
 */
class AssertionRegistry
{
    protected $assertClasses = [];
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function add($flag, $title, $description, $valid = true)
    {
        if (is_string($flag)) {
            try {
                $reflector = new \ReflectionClass($flag);
                $class = $reflector->newInstanceWithoutConstructor();
                $id = $reflector->getName();
            } catch (\ReflectionException $e) {
                if (!isset($this->container[$flag])) {
                    throw new InvalidAssertionException(sprintf('Assertion class flag "%s" is not exists in our service container', $flag));
                }
                $id = $flag;
                $class = $this->container[$flag];
            }
        } else if (is_object($flag)) {
            $class = $flag;
            $id = spl_object_hash($flag);
        } else {
            throw new InvalidAssertionException(sprintf('Assertion class flag "%s" has unsupport format "%s"', $flag,
                gettype($flag)
            ));
        }
        $interface = AssertionInterface::class;
        if (! $class instanceof $interface) {
            throw new InvalidAssertionException(sprintf('Assertion class flag "%s" should implements of interface "%s"', $flag,
                $interface));
        }
        if($this->has($id)){
            throw new InvalidAssertionException(sprintf('Assertion class flag "%s" has been exists in this registry', $id));
        }
        $this->assertClasses[$id] = ['name' => get_class($class), 'title' => $title, 'description' => $description, 'valid' => $valid];
        return $this;
    }
    public function has($flag)
    {
        return isset($this->assertClasses[$flag]);
    }
    public function removeAll()
    {
        $this->assertClasses = [];
        return $this;
    }
    public function remove($flag, $soft = false)
    {
        try {
            $item = $this->get($flag);
            if ($soft) {
                $item['valid'] = false;
                $this->assertClasses[$flag] = $item;
            } else {
                unset($this->assertClasses[$flag]);
            }
            return $item;
        }catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }
    public function getAssertionClasses()
    {
        return $this->assertClasses;
    }
    public function get($flag, $section = null)
    {
        if(!$this->has($flag)){
            throw new InvalidArgumentException(sprintf('Assertion class flag "%s" is not exists in this registry.', $flag));
        }
        if(is_null($section)) return $this->assertClasses[$flag];
        return isset($this->assertClasses[$flag][$section]) ? $this->assertClasses[$flag][$section] : null;
    }
    public function getAssertClasses()
    {
        return new Collection($this->assertClasses);
    }
}