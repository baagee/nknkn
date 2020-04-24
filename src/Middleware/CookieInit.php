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
        $cookieConfig = Config::get('cookie', []);
        if (!empty($cookieConfig)) {
            $cookieConfig['encryptkey'] = Config::get('app/secret_key', '');
            Cookie::init($cookieConfig);
            Log::info('Cookie init');
        }
        return $next($data);
    }
}
