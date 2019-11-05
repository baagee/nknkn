<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-09-05
 * Time: 11:35
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\Log\Log;
use BaAGee\Onion\Base\LayerAbstract;

/**
 * Class MiddlewareAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class MiddlewareAbstract extends LayerAbstract
{
    /**
     * @param \Closure $next
     * @param          $data
     * @return mixed
     */
    final public function exec(\Closure $next, $data)
    {
        $stime = microtime(true);
        Log::info(sprintf('%s start!', static::class));
        $return = parent::exec($next, $data);
        $etime  = microtime(true);
        $time   = number_format(($etime - $stime) * 1000, 3, '.', '');
        Log::info(sprintf('%s end! time: %sms', static::class, $time));
        return $return;
    }
}
