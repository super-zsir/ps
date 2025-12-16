<?php

namespace Imee\Exception\Operate;

class PrettyUserException extends BaseException
{
    protected $serviceCode = parent::SERVICE_CODE_PRETTY_USER;

    const DATA_NOEXIST_ERROR = ['00', '数据不存在'];
    const EXPIRE_TIME_ERROR = ['01', '请选择正确的过期时间'];
    const USER_DATA_NODEXIST_ERROR = ['02', '用户数据错误，请检查用户uid。'];
    const PRETTYUSER_RULE_ERROR = ['03', '靓号格式不正确'];
    const PRETTYUSER_EQ_UID_ERROR = ['04', '靓号不能和现有uid重复'];
    const PRETTYUSER_MORE_THAN_MAX_UID_ERROR = ['05', '靓号不能大于现有uid'];
    const USER_HAS_PRETTYUSER_ERROR = ['06', '此用户存在生效中靓号，暂时无法添加'];
    const PRETTYUSER_HAS_USED_ERROR = ['07', '此靓号已被占用，请更换其他靓号或到期后添加。'];
    const PRETTYUSER_HAS_EXPIRE_ERROR = ['08', '此数据已过期，无需操作'];
    const PRETTYUSER_EMOJI_ERROR = ['09', '运营靓号不能包含emoji'];

}
