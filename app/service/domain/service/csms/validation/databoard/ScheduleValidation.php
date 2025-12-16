<?php


namespace Imee\Service\Domain\Service\Csms\Validation\Databoard;

use Imee\Comp\Common\Validation\Validator;

class ScheduleValidation extends Validator
{
	protected function rules()
	{
		return [
			'dateline' => 'string',
		];
	}

	/**
	 * 属性
	 */
	protected function attributes()
	{
		return [
			'dateline' => '时间',
		];
	}

	/**
	 * 提示信息
	 */
	protected function messages()
	{
		return [
			'required' => '必填项没有填写',
		];
	}

	/**
	 * 返回数据结构
	 */
	protected function response()
	{
		return [
			'result' => [
				'success' => true,
				'code' => 0,
				'msg' => '',
				'total' => 1,
				'data' => [
				],
			],
		];
	}
}
