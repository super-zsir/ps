<?php

namespace Imee\Service\Domain\Service\Csms\Exception;



use Imee\Service\Domain\Service\Csms\Exception\Saas\BaseException;

class CsmsStaffException extends BaseException
{



	// 审核组
	const GROUP_NOT_EXIST = ['10', '审核组不存在'];
	const GROUP_NAME_EXIST = ['11', '审核组名称已存在'];
	const GROUP_HAS_MEMBER = ['12', '该审核组下有员工，请将员工移除后再进行删除操作'];
	const GROUP_DEL_ERROR = ['13', '删除审核组失败，请稍后重试'];
	const GROUP_DEL_EXCEPTION = ['14', '删除审核组失败，请联系管理员'];
	const GROUP_ADD_ERROR = ['15', '添加审核组失败，请稍后重试'];

	//  审核组下的审核项
	const GROUP_CHOICE_NOT_EXIST = ['20', '该审核项暂未添加'];
	const GROUP_CHOICE_DEL_ERROR = ['21', '删除审核组审核项失败'];


	const GROUP_STAFF_EXIST = ['25', '该员工已存在该审核组'];
	const GROUP_STAFF_DEL = ['27', '员工不在该审核组或已删除'];

	const USER_CHOICE_NOT_EXIST = ['11', '该员工暂未在该模块选项添加'];
	const USER_NOT_EXIST = ['12', '该员工不存在'];
	const USER_NOT_NORMAL = ['13', '该员工不可用'];
	const USER_CHOICE_EXIST = ['14', '该员工已添加过此模块选项'];
	const STAFF_NOT_EXIST = ['26', '该员工尚未在审核系统添加'];


	const MAX_TASK_NUMBER = ['10', '朋友圈和朋友圈评论模块，最大任务数不能大于100'];





}