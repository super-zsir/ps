<?php

namespace Imee\Controller\Validation\Operate\Gift;

use Imee\Comp\Common\Validation\Validator;

class TagModifyValidation extends Validator
{
	protected function rules()
	{
		return [
			'id' => 'required|integer|min:1',
			'name' => 'required|string|max:50',
			'icon' => 'string|max:255',
			'remark' => 'string|max:1000'
		];
	}


	/**
	 * 属性
	 */
	protected function attributes()
	{
		return [
			'name' => '标签名称',
			'icon' => '封面',
			'remark' => '备注',
		];
	}

	/**
	 * 提示信息
	 */
	protected function messages()
	{
		return [];
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