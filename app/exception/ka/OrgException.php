<?php

namespace Imee\Exception\Ka;

class OrgException extends BaseException
{
    protected $serviceCode = '01';

    const NO_DATA_ERROR = ['00', 'No data'];
    const GROUP_SELECT_REQUIRE = ['01', '请选择左边组织'];
    const KF_SELECT_REQUIRE = ['02', '请选择客服'];
    const ILLEGAL_ERROR = ['03', '非法操作'];
    const KF_NO_EXIST = ['04', '数据不存在'];
    const ORG_USER_EXIST_NOT_DELETE = ['05', '该部门下存在用户数据，不可以删除'];
}
