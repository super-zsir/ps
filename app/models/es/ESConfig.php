<?php

namespace Imee\Models\Es;

class ESConfig
{
    const ES = 'es';
    const MYSQL = 'mysql';
    /**
     * 是否使用es查询
     * @return bool
     */
    public static function isUseEsQuery()
    {
        return QUERY_USE_ES === true;
    }
}
