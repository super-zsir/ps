<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Databoard;

use Imee\Comp\Common\Validation\Validator;

class ModifyScheduleValidation extends Validator
{
	protected function rules(): array
	{
		return [
			'id' => 'integer|min:0|required',
			'dateline' => 'string|required',
			'turnout_num' => 'integer|min:0|required',
			'admin' => 'integer|min:0|required',
			'a_num' => 'integer|min:0|required',
			'b_num' => 'integer|min:0|required',
			'c_num' => 'integer|min:0|required',
		];
	}

	/**
	 * 属性
	 */
	protected function attributes()
	{
		return [
			'dateline' => '时间',
			'turnout_num' => '考勤次数',
			'admin' => '操作人员ID',
			'a_num' => 'A类违规量',
			'b_num' => 'B类违规量',
			'c_num' => 'C类违规量',
		];
	}

	protected function messages(): array
	{
		return [
//			'required' => '{attr} 是必填项。',
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
