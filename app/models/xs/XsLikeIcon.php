<?php

namespace Imee\Models\Xs;

class XsLikeIcon extends BaseModel
{
    protected static $primaryKey = 'id';

    const WAIT_STATUS = 1; // 待生效
    const HAVE_STATUS = 2; // 生效中
    const END_STATUS  = 3; // 已失效

}