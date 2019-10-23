<?php
/**
 * Created by Moonpie Studio.
 * User: JohnZhang
 * Date: 2019/4/26
 * Time: 14:55
 */

namespace app\common\service\security;

use Pimple\Container;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * 此类用来管理项目中所有可以使用的权限资源类，或其服务标识
 */
class ResourceRegistry
{
    protected $resourceClasses = [];
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
                    throw new InvalidAssertionException(sprintf('Resource class flag "%s" is not exists in our service container', $flag));
                }
                $id = $flag;
                $class = $this->container[$flag];
            }
        } else if (is_object($flag)) {
            $class = $flag;
            $id = spl_object_hash($flag);
        } else {
            throw new InvalidAssertionException(sprintf('Resource class flag "%s" has unsupport format "%s"', $flag,
                gettype($flag)
            ));
        }
        $interface = ResourceInterface::class;
        if (! $class instanceof $interface) {
            throw new InvalidAssertionException(sprintf('Resource class flag "%s" should implements of interface "%s"', $flag,
                $interface));
        }
        if($this->has($id)){
            throw new InvalidAssertionException(sprintf('Resource class flag "%s" has been exists in this registry', $id));
        }
        $this->resourceClasses[$id] = ['name' => get_class($class), 'title' => $title, 'description' => $description, 'valid' => $valid];
        return $this;
    }
    public function has($flag)
    {
        return isset($this->resourceClasses[$flag]);
    }
    public function removeAll()
    {
        $this->resourceClasses = [];
        return $this;
    }
    public function remove($flag, $soft = false)
    {
        try {
            $item = $this->get($flag);
            if ($soft) {
                $item['valid'] = false;
                $this->resourceClasses[$flag] = $item;
            } else {
                unset($this->resourceClasses[$flag]);
            }
            return $item;
        }catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }
    public function getResourceClasses()
    {
        return $this->resourceClasses;
    }
    public function get($flag, $section = null, $args = [])
    {
        if(!$this->has($flag)){
            throw new InvalidArgumentException(sprintf('Resource class flag "%s" is not exists in this registry.', $flag));
        }
        if(is_null($section)) return $this->resourceClasses[$flag];
        if($section == 'instance') {
            try {
                $reflector = new \ReflectionClass($flag);
                $class = $reflector->newInstanceArgs($args);
                return $class;
            } catch (\ReflectionException $e) {
                if (!isset($this->container[$flag])) {
                    throw new InvalidAssertionException(sprintf('Resource class flag "%s" is not exists in our service container', $flag));
                }
                $class = $this->container[$flag];
                return $class;
            }
        }
        return isset($this->resourceClasses[$flag][$section]) ? $this->resourceClasses[$flag][$section] : null;
    }
}