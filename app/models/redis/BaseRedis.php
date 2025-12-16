<?php

namespace Imee\Models\Redis;

class BaseRedis
{
    const REDIS_SEP = ':';
    const PREFIX = '';

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

        return self::PREFIX . self::REDIS_SEP . implode(self::REDIS_SEP, $keys);
    }
}