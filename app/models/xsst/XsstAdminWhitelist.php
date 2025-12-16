<?php

namespace Imee\Models\Xsst;

class XsstAdminWhitelist extends BaseModel
{
    public static $primaryKey = 'id';

    const TYPE_USER_MOBILE = 1;

    const DELETE_NO = 0;
    const DELETE_YES = 1;
}