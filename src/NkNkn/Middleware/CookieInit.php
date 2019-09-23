<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/9/23
 * Time: 10:29
 */

namespace BaAGee\NkNkn\Middleware;

use BaAGee\Config\Config;
use BaAGee\Cookie\Cookie;
use BaAGee\Log\Log;
use BaAGee\NkNkn\Base\MiddlewareAbstract;

/**
 * Class CookieInit
 * @package BaAGee\NkNkn\Middleware
 */
class CookieInit extends MiddlewareAbstract
{
    /**
     * @param \Closure $next
     * @param          $data
     * @return mixed
     */
    protected function handler(\Closure $next, $data)
    {
        $cookieConfig = Config::get('app/cookie');
        if (!empty($cookieConfig)) {
            Cookie::init($cookieConfig);
            Log::info('cookie init');
        }
        return $next($data);
    }
}
