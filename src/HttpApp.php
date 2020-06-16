<?php
/**
 * Desc: HttpApp
 * User: baagee
 * Date: 2020/6/16
 * Time: 下午8:21
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\Event\Event;
use BaAGee\Log\Log;
use BaAGee\NkNkn\Constant\CoreEventList;

/**
 * Class HttpApp
 * @package BaAGee\NkNkn
 */
class HttpApp extends App
{
    /**
     * @param array $params
     * @throws \Exception
     */
    public function run($params = [])
    {
        Event::trigger(CoreEventList::ROUTER_BEFORE_INIT_EVENT);

        $this->routerInit();

        Event::trigger(CoreEventList::ROUTER_AFTER_INIT_EVENT);

        Event::trigger(CoreEventList::ROUTER_BEFORE_DISPATCH_EVENT);

        list($response, $time) = self::executeTime(Router::class . '::dispatch', 0, $this->getRequestPath(), $this->getRequestMethod());

        $this->send($response);

        Log::info(sprintf('Router dispatch and action run time:%sms', $time));
    }

    /**
     * @return mixed|string
     */
    protected function getRequestPath()
    {
        return $_SERVER['PATH_INFO'] ?? '/';
    }

    /**
     * @return mixed|string
     */
    protected function getRequestMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * @throws \Exception
     */
    protected function routerInit()
    {
        list(, $time) = self::executeTime(function () {
            if (Config::get('app/is_debug', true) ||
                Router::setCachePath(AppEnv::get('RUNTIME_PATH') . DIRECTORY_SEPARATOR . 'cache') === false) {
                Router::init(include AppEnv::get('APP_PATH') . DIRECTORY_SEPARATOR . 'routes.php');
            }
            Router::setNotFound(function () {
                $file = Config::get('app/404file', '');
                if (is_file(AppEnv::get('ROOT_PATH') . DIRECTORY_SEPARATOR . 'public' . $file)) {
                    header('Location: ' . $file);
                } else {
                    http_response_code(404);
                }
            });
        });

        Log::info(sprintf('Router init time:%sms', $time));
    }

    /**
     * @param $response
     */
    protected function send($response)
    {
        echo $response;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}