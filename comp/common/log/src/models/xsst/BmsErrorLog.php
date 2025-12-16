<?php

namespace Imee\Comp\Common\Log\Models\Xsst;

class BmsErrorLog extends BaseModel
{
    public static $primaryKey = 'id';
    protected static $createTime = 'dateline';

    const STATUS_WAIT = 0;
    const STATUS_SENDING = 1;
}