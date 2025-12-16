<?php

namespace Imee\Controller\Validation\Luckygift;

use Imee\Comp\Common\Validation\Validator;

class RateValidation extends Validator
{
	protected function rules()
	{
		return [
			'property' =>  'required|integer',
			'rate'     =>  'required|integer',
			'weight'   =>  'required|integer',
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
			'weight'   => '权重',
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