<?php

namespace BaAGee\NkNkn\Base;

use BaAGee\Log\Log;

abstract class EventAbstract
{
    use TimerTrait;

    abstract protected function run($args = null);

    final public function main()
    {
        Log::info(sprintf('%s start!', static::class));
        list(, $time) = self::executeTime([$this, 'run'], 0, func_get_args());
        Log::info(sprintf('%s end! time: %sms', static::class, $time));
    }
}
