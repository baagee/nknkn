<?php

namespace BaAGee\NkNkn;

use ArrayAccess;
use Closure;
use Countable;
use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * 容器管理类
 */
class Container implements ContainerInterface, ArrayAccess, Countable
{
    /**
     * 容器对象实例
     * @var Container
     */
    protected static $instance;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [];

    /**
     * 获取当前容器的实例（单例）
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * 禁止创建新容器
     * Container constructor.
     */
    final private function __construct()
    {
    }

    /**
     * 克隆也是返回他自己
     * @return Container
     */
    final public function __clone()
    {
        return static::$instance;
    }

    /**
     * 获取容器中的对象实例 不存在则创建
     * @param string     $abstract    类名或者标识
     * @param array|true $vars        变量
     * @param bool       $newInstance 是否每次创建新的实例
     * @return mixed
     * @throws Exception
     */
    public static function pull(string $abstract, array $vars = [], bool $newInstance = false)
    {
        return static::getInstance()->make($abstract, $vars, $newInstance);
    }

    /**
     * 获取容器中的对象实例
     * @param string $abstract 类名或者标识
     * @return mixed
     * @throws Exception
     */
    public function get($abstract)
    {
        if ($this->has($abstract)) {
            return $this->make($abstract);
        }

        throw new Exception('class not exists: ' . $abstract);
    }

    /**
     * 绑定一个类、闭包、实例、接口实现到容器
     * @access public
     * @param string|array $abstract 类标识、接口
     * @param mixed        $concrete 要绑定的类、闭包或者实例
     * @return $this
     */
    public function bind($abstract, $concrete = null)
    {
        if (is_array($abstract)) {
            foreach ($abstract as $key => $val) {
                $this->bind($key, $val);
            }
        } elseif ($concrete instanceof Closure) {
            $this->bind[$abstract] = $concrete;
        } elseif (is_object($concrete)) {
            $this->instance($abstract, $concrete);
        } else {
            $abstract = $this->getAlias($abstract);
            $this->bind[$abstract] = $concrete;
        }

        return $this;
    }

    /**
     * 根据别名获取真实类名
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];

            if (is_string($bind)) {
                return $this->getAlias($bind);
            }
        }

        return $abstract;
    }

    /**
     * 绑定一个类实例到容器
     * @access public
     * @param string $abstract 类名或者标识
     * @param object $instance 类的实例
     * @return $this
     */
    public function instance(string $abstract, $instance)
    {
        $abstract = $this->getAlias($abstract);

        $this->instances[$abstract] = $instance;

        return $this;
    }

    /**
     * 判断容器中是否存在类及标识
     * @access public
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bind[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * 判断容器中是否存在类及标识
     * @access public
     * @param string $name 类名或者标识
     * @return bool
     */
    public function has($name): bool
    {
        return $this->bound($name);
    }

    /**
     * 判断容器中是否存在对象实例
     * @access public
     * @param string $abstract 类名或者标识
     * @return bool
     */
    public function exists(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);

        return isset($this->instances[$abstract]);
    }

    /**
     * 创建类的实例 已经存在则直接获取
     * @param string $abstract    类名或者标识
     * @param array  $vars        变量
     * @param bool   $newInstance 是否每次创建新的实例
     * @return mixed
     * @throws Exception
     */
    public function make(string $abstract, array $vars = [], bool $newInstance = false)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && !$newInstance) {
            return $this->instances[$abstract];
        }

        if (isset($this->bind[$abstract]) && $this->bind[$abstract] instanceof Closure) {
            $object = $this->invokeFunction($this->bind[$abstract], $vars);
        } else {
            $object = $this->invokeClass($abstract, $vars);
        }

        if (!$newInstance) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 删除容器中的对象实例
     * @access public
     * @param string $name 类名或者标识
     * @return void
     */
    public function delete($name)
    {
        $name = $this->getAlias($name);

        if (isset($this->instances[$name])) {
            unset($this->instances[$name]);
        }
    }

    /**
     * 执行函数或者闭包方法 支持参数调用
     * @param string|array|Closure $function 函数或者闭包
     * @param array                $vars     参数
     * @return mixed
     * @throws Exception
     */
    public function invokeFunction($function, array $vars = [])
    {
        try {
            $reflect = new ReflectionFunction($function);

            $args = $this->bindParams($reflect, $vars);

            if ($reflect->isClosure()) {
                // 解决在`php7.1`调用时会产生`$this`上下文不存在的错误 (https://bugs.php.net/bug.php?id=66430)
                return $function->__invoke(...$args);
            } else {
                return $reflect->invokeArgs($args);
            }
        } catch (ReflectionException $e) {
            // 如果是调用闭包时发生错误则尝试获取闭包的真实位置
            if (isset($reflect) && $reflect->isClosure() && $function instanceof Closure) {
                $function = "{Closure}@{$reflect->getFileName()}#L{$reflect->getStartLine()}-{$reflect->getEndLine()}";
            } else {
                $function .= '()';
            }
            throw new Exception('function not exists: ' . $function, 0, $e);
        }
    }

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @param string $class 类名
     * @param array  $vars  参数
     * @return mixed|object
     * @throws Exception
     */
    public function invokeClass(string $class, array $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);

            if ($reflect->hasMethod('__make')) {
                $method = new ReflectionMethod($class, '__make');

                if ($method->isPublic() && $method->isStatic()) {
                    $args = $this->bindParams($method, $vars);
                    return $method->invokeArgs(null, $args);
                }
            }

            $constructor = $reflect->getConstructor();

            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new Exception('class not exists: ' . $class);
        }
    }

    /**
     * 绑定参数
     * @param \ReflectionMethod|\ReflectionFunction $reflect 反射类
     * @param array                                 $vars    参数
     * @return array
     * @throws Exception
     */
    protected function bindParams($reflect, array $vars = []): array
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }

        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();
        $args = [];

        foreach ($params as $param) {
            $name = $param->getName();
            $lowerName = strtolower($name);
            $class = $param->getClass();

            if ($class) {
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } elseif (0 == $type && isset($vars[$name])) {
                $args[] = $vars[$name];
            } elseif (0 == $type && isset($vars[$lowerName])) {
                $args[] = $vars[$lowerName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException('method param miss:' . $name);
            }
        }

        return $args;
    }

    /**
     * 创建工厂对象实例
     * @param string $className 工厂类名
     * @param array  ...$args
     * @return mixed|object
     * @throws Exception
     */
    public static function factory(string $className, ...$args)
    {
        if (class_exists($className)) {
            return Container::getInstance()->invokeClass($className, $args);
        }

        throw new Exception('class not exists:' . $className);
    }

    /**
     * 获取对象类型的参数值
     * @param string $className 类名
     * @param array  $vars      参数
     * @return mixed
     * @throws Exception
     */
    protected function getObjectParam(string $className, array &$vars)
    {
        $array = $vars;
        $value = array_shift($array);

        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }

        return $result;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->bind($name, $value);
    }

    /**
     * @param $name
     * @return mixed|object
     * @throws Exception
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name): bool
    {
        return $this->exists($name);
    }

    /**
     * @param $name
     */
    public function __unset($name)
    {
        $this->delete($name);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * @param mixed $key
     * @return mixed|object
     * @throws Exception
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    /**
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->instances);
    }
}
