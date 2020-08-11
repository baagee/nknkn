<?php
/**
 * Desc: 路由
 * User: baagee
 * Date: 2019/3/29
 * Time: 下午10:58
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\Event\Event;
use BaAGee\Log\Log;
use BaAGee\NkNkn\Base\ActionAbstract;
use BaAGee\NkNkn\Base\TraitFunc\TimerTrait;
use BaAGee\Onion\Onion;
use BaAGee\Router\Base\RouterAbstract;
use BaAGee\NkNkn\Middleware\CookieInit;
use BaAGee\NkNkn\Middleware\SessionInit;

/**
 * Class Router
 * @package BaAGee\NkNkn
 */
final class Router extends RouterAbstract
{
    use TimerTrait;

    const ROUTER_BEFORE_INIT_EVENT     = 'CORE_ROUTER_BEFORE_INIT_EVENT';
    const ROUTER_AFTER_INIT_EVENT      = 'CORE_ROUTER_AFTER_INIT_EVENT';
    const ROUTER_BEFORE_DISPATCH_EVENT = 'CORE_ROUTER_BEFORE_DISPATCH_EVENT';
    const ROUTER_AFTER_DISPATCH_EVENT  = 'CORE_ROUTER_AFTER_DISPATCH_EVENT';

    /**
     * 匹配到路由后调用方法
     * @param \Closure|string $callback
     * @param array           $params
     * @param string          $method
     * @param array           $other
     * @return mixed
     * @throws \Exception
     */
    protected static function call($callback, $params, $method, $other)
    {
        $callback = self::getCallback($callback);
        $middlewareList = self::getMiddlewareList($other);
        // 路由匹配结束后
        Event::trigger(self::ROUTER_AFTER_DISPATCH_EVENT);
        return self::eatingOnion(self::getRequestData($method, $params), $middlewareList, $callback);
    }

    /**
     * 获取中间件列表
     * @param $other
     * @return mixed
     */
    protected static function getMiddlewareList($other): array
    {
        $other = (array)$other;
        //前面追加Cookie session初始化
        $commonMiddleware = [];
        if (Config::get('cookie/enable', false) == true) {
            $commonMiddleware[] = CookieInit::class;
        }
        if (Config::get('session/enable', false) == true) {
            $commonMiddleware[] = SessionInit::class;
        }
        if (!empty($commonMiddleware)) {
            array_unshift($other, ...$commonMiddleware);
        }
        return $other;
    }

    /**
     * 获取回调函数
     * @param $callback
     * @return array|ActionAbstract|\Closure|false|string|string[]
     * @throws \Exception
     */
    protected static function getCallback($callback)
    {
        if ($callback instanceof \Closure) {
        } elseif (is_string($callback) || is_array($callback)) {
            if (is_string($callback)) {
                if (strpos($callback, '@') !== false) {
                    $callback = explode('@', $callback);// 控制器@action
                    $controllerName = $className = $callback[0];// 控制器名字 类名字
                    $actionName = $methodName = $callback[1];// 动作名字 执行类的方法
                } elseif (class_exists($callback) && is_subclass_of($callback, ActionAbstract::class)) {
                    // $actionClassName
                    $className = $callback;
                    $methodName = 'main';
                    $tmp = explode('\\', $className);
                    $actionName = array_pop($tmp);
                    $controllerName = array_pop($tmp);
                } else {
                    throw new \Exception('不合法的callback路由回调');
                }
            } else {
                $controllerName = $className = $callback[0];
                $actionName = $methodName = $callback[1];
            }

            if (!class_exists($className)) {
                throw new \Exception(sprintf('[%s]类不存在', $className));
            }
            $obj = new $className();
            if (!method_exists($obj, $methodName)) {
                throw new \Exception(sprintf('[%s]类的[%s]方法不存在', $className, $methodName));
            }
            $c = explode('\\', $className);
            $moduleName = count($c) >= 4 ? (in_array(strtolower($c[1]), [
                'action', 'controller', 'model', 'view'
            ]) ? false : $c[1]) : false;
            $arr = explode('\\', $controllerName);
            $controllerName = array_pop($arr);
            // var_dump($moduleName, $controllerName, $actionName);
            AppEnv::set('MODULE', $moduleName);
            AppEnv::set('CONTROLLER', $controllerName);
            AppEnv::set('ACTION', $actionName);
            $callback = [$obj, $methodName];
        } else {
            throw new \Exception('不合法的callback路由回调');
        }
        return $callback;
    }

    /**
     * 获取请求参数
     * @param string $method 请求方法
     * @param array  $params 路由中的参数
     * @return array
     */
    protected static function getRequestData($method, $params)
    {
        $contentType = strtolower($_SERVER['CONTENT_TYPE']);
        if (strpos($contentType, 'application/json') !== false) {
            // json
            $requestParams = json_decode(file_get_contents('php://input'), true) ?? [];
        } elseif (strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false) {
            //xml
            $xml = simplexml_load_string(file_get_contents('php://input'));
            if (!is_null($xml)) {
                $requestParams = json_decode(json_encode($xml), true) ?? [];
            } else {
                $requestParams = [];
            }
        } else {
            // form-data or x-www-form-urlencoded
            if (strtolower($method) == 'get') {
                $requestParams = $_GET;
            } elseif (strtolower($method) == 'post') {
                $requestParams = $_POST;
            } else {
                $requestParams = [];
            }
        }
        $params = array_merge($requestParams, $params);
        return $params;
    }

    /**
     * @param array $data  request params
     * @param array $layer middleware list
     * @param       $callback
     * @return mixed
     */
    protected static function eatingOnion($data, $layer, $callback)
    {
        return (new Onion())->send($data)->through($layer)->then(function ($request) use ($callback) {
            list($res, $time) = self::executeTime(function ($callback, $request) {
                return call_user_func($callback, $request);
            }, 0, $callback, $request);
            Log::info(sprintf('Action execute time：%sms', $time));
            return $res;
        });
    }

    /**
     * 初始化路由
     * @param array $routers
     * @throws \Exception
     */
    public static function init(array $routers)
    {
        foreach ($routers as $path => $router) {
            self::add($router['method'], $path, $router['callback'], $router['middleware'] ?? []);
        }
    }
}
