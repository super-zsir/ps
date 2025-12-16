<?php


namespace Imee\Service\Domain\Service\Csms\Exception\Saas;

class ServicerException extends BaseException
{
    protected $serviceCode = '15';

    const SERVICER_LIST_ERROR = ['01', '加载服务商列表错误'];
    const SERVICER_EDIT_ERROR = ['02', '服务商新增或修改错误'];
    const SERVICER_SCENE_ERROR = ['03', '加载服务商配置列表错误'];
    const SERVICER_SCENE_EDIT_ERROR = ['04', '服务商配置新增或修改错误'];
    const FIELD_SCENE_ERROR = ['05', '加载审核字段场景列表错误'];
    const FIELD_SCENE_EDIT_ERROR = ['06', '审核字段场景新增或修改错误'];
    const FIELD_SCENE_DEL_ERROR = ['07', '审核字段场景删除错误'];
    const SERVICER_NOT_EXIST = ['08', '服务商不存在'];
    const SERVICER_ALREADY_EXIST = ['09', '服务商标记已存在'];
    const SERVICER_HAVE_STAGE = ['10', '请先删除对应服务场景'];
    const FIELD_HAVE_SCENE = ['11', '当前字段已有配置，请在已有配置中添加'];
	const UNVALID_FIELD_SCENE = ['12', '当前字段场景不存在'];
	const UNVALID_FIELD_SCENE_ERROR = ['13', '当前机审代替人审不存在'];
}
