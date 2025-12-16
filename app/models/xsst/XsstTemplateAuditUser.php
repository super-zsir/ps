<?php

namespace Imee\Models\Xsst;

class XsstTemplateAuditUser extends BaseModel
{
    protected static $primaryKey = 'id';

    const STATE_START = 0;
    const STATE_END = 1;

    public static function checkUserName($userName): array
    {
        return self::findOneByWhere([
            ['user_name', '=', $userName],
            ['state', '=', self::STATE_START]
        ]);
    }
}