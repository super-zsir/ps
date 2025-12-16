<?php

namespace Imee\Exception\Ka;

class UserException extends BaseException
{
    protected $serviceCode = '01';

    const USER_SELECT_ERROR = ['00', '请勾选用户'];
    const ILLEGAL_ERROR = ['01', '非法操作'];
    const COMMON_NO_DATA = ['02', '没有数据'];
    const COMMON_KF_NOT_EXIST = ['03', '该客服用户不存在'];
    const COMMON_FIELD_VAR_EXiST = ['04', '%s 已存在'];
    const USER_BATCH_KF_SELECT_BIG = ['05', '选择只看大号在分配客服'];
    const USER_NOT_EXIST = ['06', '该用户不存在'];
    const USER_BUILD_AL_STATUS_UID = ['07', '该用户不存在'];
    const USER_BUILD_AL_STATUS_FRIEND_DATE = ['08', '请填写建联时间'];
    const USER_UID_NUMBER_NOT_GT = ['09', 'UID数量不能大于 %s 个'];
    const FIELD_NOT_EMPTY = ['10', '%s 不能为空'];
    const COMMON_KF_DATA_EXIST = ['11', '该客服数据已存在'];
    const USER_SELECT_NUM_MAX = ['12', '勾选用户不能超过 %s 个'];
    const IM_STATUS_INVALID = ['13', '请选择发送/拒绝'];
}
