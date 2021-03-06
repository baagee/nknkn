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
use \BaAGee\NkNkn\Base\TraitFunc\TimerTrait;

/**
 * Class MiddlewareAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class MiddlewareAbstract extends LayerAbstract
{
    use TimerTrait;

    /**
     * @param \Closure $next
     * @param          $data
     * @return mixed
     * @throws \Exception
     */
    final public function exec(\Closure $next, $data)
    {
        Log::info(sprintf('%s start!', static::class));
        list($return, $time) = self::executeTime(function ($next, $data) {
            return parent::exec($next, $data);
        }, 0, $next, $data);
        Log::info(sprintf('%s end! time: %sms', static::class, $time));
        return $return;
    }
}
