<?php

namespace Imee\Models\Xsst;

class XsstAwardKingdeeRecord extends BaseModel
{
    public static $primaryKey = 'id';

    const DELETE_YES = 1;
    const DELETE_NO = 0;

    const STATUS_INIT = 0;//已提交数据
    const STATUS_SUBMIT = 1;//已提交云之家
    const STATUS_PASS = 2;//云之家审核通过
    const STATUS_FAIL = 3;//云之家审核失败


    const HANDLE_NO = 1;
    const HANDLE_YES = 2;
}