<?php


namespace Imee\Service\Domain\Service\Csms\Exception\Saas;

class AuditDbException extends BaseException
{
    protected $serviceCode = '14';

    const AUDIT_LIST_ERROR = ['01', '加载审核项列表错误'];
    const AUDIT_EDIT_ERROR = ['02', '审核项新增或修改错误'];
    const AUDIT_FEILD_LIST_ERROR = ['03', '加载审核项字段列表错误'];
    const AUDIT_FEILD_EDIT_ERROR = ['04', '审核项字段新增或修改错误'];
    const AUDIT_STAGE_LIST_ERROR = ['05', '加载审核项场景列表错误'];
    const AUDIT_STAGE_EDIT_ERROR = ['06', '审核项场景新增或修改错误'];
    const AUDIT_STAGE_ALREADY_ADD = ['07', '审核项场景已存在'];
    const AUDIT_ALREADY_ADD = ['08', '审核项已存在'];
    const AUDIT_FIELD_ALREADY_ADD = ['09', '审核字段已存在'];
    const AUDIT_FIELD_ALREADY_EXIST = ['10', '请先下线对应审核字段'];
    const AUDIT_STAGE_ALREADY_EXIST = ['11', '请先下线对应审核阶段'];
    const AUDIT_INFO_TOO_LONG = ['12', '回调格式过长'];
    const AUDIT_NOT_EXIST = ['13', '审核项不存在'];
    const AUDIT_WITHOUT_SETTING = ['14', '缺少阶段配置'];
    const AUDIT_WITHOUT_STAGE = ['15', '审核字段不存在'];
    const AUDIT_HAVE_DATA = ['16', '当前阶段下有待审数据不可修改'];
    const AUDIT_STAGE_NOT_DELETE = ['17', '审核项已使用不可更改标识'];
	const MACHINE_ERROR = ['18', '机审代替人审新增或修改错误'];
	const MACHINE_LIST_ERROR = ['19', '加载机审代替人审列表错误'];
	const CONFIG_ERROR = ['20', '配置加载错误'];
}
