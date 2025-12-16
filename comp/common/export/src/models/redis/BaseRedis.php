<?php

namespace Imee\Comp\Common\Export\Models\Redis;

class BaseRedis
{
    const REDIS_SEP = ':';

    public static function key(): string
    {
        $args = func_get_args();
        $keys = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $keys[] = md5(implode(self::REDIS_SEP, $arg));
            } elseif (is_scalar($arg)) {
                $keys[] = $arg;
            } else {
                throw new \Exception('parameters only support scalar and array');
            }
        }

        return implode(self::REDIS_SEP, $keys);
    }
}