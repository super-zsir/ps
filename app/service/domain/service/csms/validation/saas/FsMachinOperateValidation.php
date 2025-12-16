<?php

namespace Imee\Service\Domain\Service\Csms\Validation\Saas;

use Imee\Comp\Common\Validation\Validator;

class FsMachinOperateValidation extends Validator
{
	protected function rules()
	{
		return [
			'field_scene_id' => 'required|int',
			'scene_ids' => 'required|array',
			'valid_state' => 'required|int',
			'type' => 'required|int',
			'sort' => 'required|int',
		];
	}

	/**
	 * 属性
	 */
	protected function attributes()
	{
		return [
			'field_scene_id' => '字段场景',
			'scene_ids' => '生效场景',
			'valid_state' => '生效机审结果',
			'type' => '是否兼容其他检测结果',
			'sort' => '优先级',
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
				'data' => null,
			],
		];
	}
}
