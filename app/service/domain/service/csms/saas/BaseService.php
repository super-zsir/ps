<?php

namespace Imee\Service\Domain\Service\Csms\Saas;

class BaseService
{
    /**
     * @var static
     */
    public static $server;

    /**
     * 实例化
     * @param array $condition
     * @param bool $force_new 是否强制获取新的对象
     * @return static
     */
    public static function getInstance(array $condition = [], bool $force_new = false)
    {
        if (!self::$server instanceof static || $force_new) {
            if (!empty($condition)) {
                self::$server = new static($condition);
            } else {
                self::$server = new static();
            }
        }
        return self::$server;
    }
}