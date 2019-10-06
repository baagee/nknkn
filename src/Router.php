<?php
/**
 * Desc: 路由
 * User: baagee
 * Date: 2019/3/29
 * Time: 下午10:58
 */

namespace BaAGee\NkNkn;

use BaAGee\Log\Log;
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
                $callback = explode('@', $callback);
            }
            list($controller, $action) = $callback;
            if (class_exists($controller)) {
                $obj = new $controller();
                if (method_exists($obj, $action)) {
                    $cA = explode('\\', $controller);
                    AppEnv::set('CONTROLLER', end($cA));
                    AppEnv::set('ACTION', $action);
                    $callback = [$obj, $action];
                } else {
                    throw new \Exception(sprintf('[%s]控制器的[%s]方法不存在', $controller, $action));
                }
            } else {
                throw new \Exception(sprintf('[%s]控制器不存在', $controller));
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
        Log::info('获取到请求参数: ' . json_encode($params, true));
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
            Log::info('action input：' . json_encode($request, JSON_UNESCAPED_UNICODE));
            $startTime = microtime(true);
            $res       = call_user_func($callback, $request);
            $endTime   = microtime(true);
            Log::info('action output：' . json_encode($res, JSON_UNESCAPED_UNICODE));
            Log::info('action执行时间：' . (($endTime - $startTime) * 1000) . 'ms');
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
