<?php
namespace Imee\Exception\Audit;

class RiskCheckException extends BaseException
{
    protected $serviceCode = self::RISKCHECK;

    const GET_DATA_FAILED = ['00', '获取数据失败'];
    const RECORD_HANDLED = ['01', '该记录已处理'];
    const CHECK_FAILED = ['02', '核查操作失败'];
    const CHECK_DID_FORBIDDEN = ['03', '无did封禁数据'];
    const DID_DATA_NOEXIST_ERROR = ['04', '无可解封数据'];
    const DATA_DONE_ERROR = ['05', '该记录已处理'];
    const USER_NOEXIST_ERROR = ['06', '用户不存在'];
    const CURRENT_STATE_NOALLOW_HANDLE_ERROR = ['07', '非禁止登录的不可操作封设备'];
    const USER_NO_SAFE_MOBILE_ERROR = ['08', 'TA没有安全手机号'];
    const CHECK_REASON_ERROR = ['09', '请选择正确的操作原因'];
    const FORBIDDEN_LOG_NOT_FOUND = ['10', '封禁记录不存在'];
    const CHANGE_STATUS_ERROR = ['11', '当前状态不允许扭转'];
}
