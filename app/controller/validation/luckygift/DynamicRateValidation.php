<?php

namespace Imee\Controller\Validation\Luckygift;

use Imee\Comp\Common\Validation\Validator;

class DynamicRateValidation extends Validator
{
	protected function rules()
	{
		return [
			'start'    =>  'integer',
			'end'      =>  'integer',
			'change'   =>  'required|integer',
			'rate'     =>  'required|integer',
			'expectation'   =>  'required',
			'property' =>  'required|integer',
		];
	}

	/**
	 * 属性
	 */
	protected function attributes()
	{
		return [
			'property' => '属性',
			'rate'     => '倍数',
			'expectation'   => '期望',
			'change'   => '调整方式',
			'start'    => '开始区间',
			'end'      => '结束区间',
		];
	}

	/**
	 * 提示信息
	 */
	protected function messages()
	{
		return [
			'integer' => 'invalid param',
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
				'data' => null,
			],
		];
	}
}