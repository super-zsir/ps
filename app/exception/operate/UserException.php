<?php

namespace Imee\Exception\Operate;

class UserException extends BaseException
{
    protected $serviceCode = '01';

    const GET_ACCOUNT_LIST = ['00', '用户账户列表失败'];
    const API_TRANSFER = ['01', '接口调用出错'];
    const LANGUAGE_MODIFY = ['02', '语言修改失败'];
    const LANGUAGE_MODIFY_SYNC = ['03', '语言修改同步失败:%s'];
    const USER_LEVEL_TYPE = ['04', 'error level type'];
    const USER_ACTIVE_MODIFY = ['05', '用户活跃度修改失败 %s'];
    const USER_GET_POPULARITY = ['06', '用户人气值获取失败 %s'];
    const USER_MODIFY_POPULARITY = ['07', '用户人气值修改失败 %s'];
    const USER_GET_VIP = ['08', '用户VIP值获取失败 %s'];
    const USER_MODIFY_VIP = ['09', '用户VIP值修改失败 %s'];
    const USER_GET_NOBILITY = ['10', '用户爵位值获取失败 %s'];
    const USER_MODIFY_NOBILITY = ['11', '用户爵位值修改失败 %s'];
    const USER_GET_ACCOUNT_HISTORY = ['12', '用户账户历史获取失败 %s'];

    const PUNISH_LOG_VERIFY_AGAIN = ['13', '审核过的数据不能再次审核'];
    const PUNISH_USER_MODIFY_MONEY = ['14', '用户加钱失败 %s'];
    const PUNISH_USER_NO_SELECT_OP_TYPE = ['15', '请选择操作类型'];

    const USER_NO_EXIST = ['16', '用户不存在'];
	const PUNISH_USER_PANALIZE_MONEY = ['17', '用户罚钱失败 %s'];
	const USER_LANGUAGE_PERMISSION = ['18', '用户无该语言权限 %s'];
    const USER_GET_PERFECT = ['19', '用户靓号获取失败 %s'];

}
