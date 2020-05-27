<?php

namespace BaAGee\NkNkn\Base;

use BaAGee\Log\Log;
use \BaAGee\NkNkn\Base\TraitFunc\TimerTrait;

/**
 * Class EventAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class EventAbstract
{
    use TimerTrait;

    /**
     * @param null $args
     * @return mixed
     */
    abstract protected function run($args = null);

    /**
     * @throws \Exception
     */
    final public function main()
    {
        Log::info(sprintf('%s start!', static::class));
        list(, $time) = self::executeTime([$this, 'run'], 0, func_get_args());
        Log::info(sprintf('%s end! time: %sms', static::class, $time));
    }
}
