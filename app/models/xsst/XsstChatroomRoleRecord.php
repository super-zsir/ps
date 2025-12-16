<?php

namespace Imee\Models\Xsst;

class XsstChatroomRoleRecord extends BaseModel
{
    protected static $primaryKey = 'id';

    const ACTION_ADD = 'roleAdd';
    const ACTION_CANCEL = 'roleRemove';
}