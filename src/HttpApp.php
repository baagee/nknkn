<?php
/**
 * Desc: HttpApp
 * User: baagee
 * Date: 2020/6/16
 * Time: 下午8:21
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\DebugTrace\DebugTrace;
use BaAGee\DebugTrace\TraceCollector;
use BaAGee\Event\Event;
use BaAGee\Log\Log;
use BaAGee\MySQL\SqlRecorder;

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
        Event::trigger(Router::ROUTER_BEFORE_INIT_EVENT);

        $this->routerInit();

        Event::trigger(Router::ROUTER_AFTER_INIT_EVENT);

        Event::trigger(Router::ROUTER_BEFORE_DISPATCH_EVENT);

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
        if (PHP_SAPI != 'cli' && Config::get('app/is_debug', true)) {
            $response = $this->appendDebugTrace($response);//开发模式输出调试信息
        }
        echo $response;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * 在页面后追加调试信息输出
     * @param $response
     * @return string
     */
    protected function appendDebugTrace($response)
    {
        $headers = headers_list();
        foreach ($headers as $header) {
            $header = str_replace(array(" ", "　", "\t", "\n", "\r"), array("", "", "", "", ""), $header);
            if (stripos($header, 'Content-Type:text/html') !== false) {//只有输出html时才展示
                $allSqls = SqlRecorder::getAllFullSql();
                foreach ($allSqls as $sql) {
                    $t = explode('.', number_format($sql['startTime'], 4, '.', ''));
                    $time = date('H:i:s', $t[0]) . '.' . ($t[1] ?? '0000');
                    $info = sprintf('[%s] %s %.2fms', $time, $sql['fullSql'], (($sql['endTime'] - $sql['startTime']) * 1000));
                    TraceCollector::addLog(TraceCollector::TRACE_TYPE_SQL, $info);
                }
                $output = DebugTrace::output();
                if (is_string($output) && !empty($output)) {
                    // 追加trace调试信息
                    $pos = strripos($response, '</body>');
                    if (false !== $pos) {
                        $response = substr($response, 0, $pos) . $output . substr($response, $pos);
                    } else {
                        $response .= $output;
                    }
                }
                break;
            }
        }
        return $response;
    }
}
