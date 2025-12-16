<?php

namespace Imee\Exception\Cs;

class CommonException extends BaseException
{
	protected $serviceCode = '06';

	const RECORD_NOT_FOUND = ['00', '该记录不存在'];
	const CREATE_FAILED = ['01', '创建失败'];
	const DELETE_FAILED = ['02', '删除失败'];
	const MODIFY_FAILED = ['03', '修改失败'];
	const DUPLICATE_RECORD = ['04', '该记录已存在'];
}