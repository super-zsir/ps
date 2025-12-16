<?php

namespace Imee\Models\Xsst;

class XsstGiftOperationLog extends BaseModel
{
    public static $createTime = 'dateline';

    const TYPE_ADD = 1;
    const TYPE_UPDATE = 2;
}