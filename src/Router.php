<?php
/**
 * Desc: 路由
 * User: baagee
 * Date: 2019/3/29
 * Time: 下午10:58
 */

namespace BaAGee\NkNkn;

use BaAGee\Log\Log;
use BaAGee\NkNkn\Base\ActionAbstract;
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
    /**
     * @param \Closure|string $callback
     * @param array           $params
     * @param string          $method
     * @param array           $other
     * @return mixed
     * @throws \Exception
     */
    protected static function call($callback, $params, $method, $other)
    {
        if ($callback instanceof \Closure) {
        } elseif (is_string($callback) || is_array($callback)) {
            if (is_string($callback)) {
                if (strpos($callback, '@') !== false) {
                    // 控制器@action
                    $callback = explode('@', $callback);
                    // 控制器名字 类名字
                    $controllerName = $className = $callback[0];
                    // 动作名字 执行类的方法
                    $actionName = $methodName = $callback[1];
                } elseif (class_exists($callback) && is_subclass_of($callback, ActionAbstract::class)) {
                    // $actionClassName
                    $className      = $callback;
                    $methodName     = 'main';
                    $tmp            = explode('\\', $className);
                    $actionName     = array_pop($tmp);
                    $controllerName = array_pop($tmp);
                } else {
                    throw new \Exception('不合法的callback路由回调');
                }
            } else {
                $controllerName = $className = $callback[0];
                $actionName     = $methodName = $callback[1];
            }

            if (class_exists($className)) {
                $obj = new $className();
                if (method_exists($obj, $methodName)) {
                    $controllerName = array_pop(explode('\\', $controllerName));
                    AppEnv::set('CONTROLLER', $controllerName);
                    AppEnv::set('ACTION', $actionName);
                    $callback = [$obj, $methodName];
                } else {
                    throw new \Exception(sprintf('[%s]类的[%s]方法不存在', $className, $methodName));
                }
            } else {
                throw new \Exception(sprintf('[%s]类不存在', $className));
            }
        } else {
            throw new \Exception('不合法的callback路由回调');
        }
        //前面追加Cookie session初始化
        $commonMiddleware = [CookieInit::class, SessionInit::class];
        array_unshift($other, ...$commonMiddleware);
        return self::eatingOnion(self::getRequestData($method, $params), $other, $callback);
    }

    /**
     * @param $method
     * @param $params
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
        Log::info('Request params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        return $params;
    }

    /**
     * @param $data
     * @param $layer
     * @param $callback
     * @return mixed
     */
    protected static function eatingOnion($data, $layer, $callback)
    {
        $onion = new Onion();
        return $onion->send($data)->through($layer)->then(function ($request) use ($callback) {
            Log::info('Action input：' . json_encode($request, JSON_UNESCAPED_UNICODE));
            $startTime = microtime(true);
            $res       = call_user_func($callback, $request);
            $endTime   = microtime(true);
            $time      = number_format(($endTime - $startTime) * 1000, 3, '.', '');
            Log::info(sprintf('Action output %s  execute time：%sms', json_encode($res, JSON_UNESCAPED_UNICODE), $time));
            return $res;
        });
    }

    /**
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
