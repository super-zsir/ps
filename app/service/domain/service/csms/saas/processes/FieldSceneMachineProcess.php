<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsFieldScene;
use Imee\Models\Xss\CsmsFieldSceneMachine;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsServicer;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FieldSceneMachineContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FsMachineContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ServicerException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 审核字段场景
 */
class FieldSceneMachineProcess
{
	use CsmsTrait;
	use UserInfoTrait;

	/**
	 * 审核项列表
	 * @param FieldSceneMachineContext $context
	 * @return array
	 */
	public function fieldSceneMachineList(FieldSceneMachineContext $context): array
	{
		$condition = $this->filterConditions($context);
		$condition['columns'] = ['id', 'field_scene_id', 'scene_ids', 'valid_state', 'type', 'sort', 'state', 'create_time'];
		$list = CsmsFieldSceneMachine::handleList($condition);
		$total = $this->fieldSceneMachineTotal($condition);
		if ($list) {
			// 服务商场景信息
			$sids = array_column($list, 'scene_ids');
			$scenes = [];
			foreach ($sids as $scene_id) {
				$scenes = array_merge($scenes, explode(',', $scene_id));
			}
			$scenes = array_unique($scenes);
			$scene = CsmsServicerScene::handleList([
				'columns' => ['CsmsServicerScene.id', 'CsmsServicerScene.name', 'CsmsServicerScene.sid'],
				'id_array' => $scenes,
				'alias' => 'CsmsServicerScene',
			]);
			$scene_list = array_column($scene, null, 'id');
			// 审核字段信息
			$fsids = array_column($list, 'field_scene_id');
			$fids = CsmsFieldScene::handleList(array(
				'id_array' => $fsids,
				'columns' => ['fid', 'id']
			));
			$fsfields = array_column($fids, 'fid', 'id');
			$fields = CsmsChoiceField::handleList([
//                'columns' => ['id', 'field', 'db_name', 'table_name'],
				'id_array' => array_values($fsfields)
			]);
			$fields = array_column($fields, null, 'id');
			// 审核项
			$cids = array_column($fields, 'cid');
			$choices = CsmsChoice::handleList(array(
				'id_array' => $cids,
				'columns' => 'choice_name, id',
			));
			$choices = array_column($choices, 'choice_name', 'id');
			// 服务商
			$servicers = CsmsServicer::handleList([
				'columns' => ['id', 'name']
			]);
			$servicers = array_column($servicers, 'name', 'id');
			foreach ($list as &$v) {
				$new_sids = explode(',', $v['scene_ids']);
				$v['servicer'] = implode(',', array_unique(array_filter(array_map(function ($item) use ($servicers, $scene_list) {
					return $servicers[$scene_list[$item]['sid']] ?? '';
				}, $new_sids))));
				$v['type_name'] = isset($fsfields[$v['field_scene_id']]) ? (isset($fields[$fsfields[$v['field_scene_id']]]) ? "{$fields[$fsfields[$v['field_scene_id']]]['type']}" : '') : '';
				$v['scene'] = implode(',', array_filter(array_map(function ($item) use ($scene_list) {
					return isset($scene_list[$item]) ? $scene_list[$item]['name'] : '';
				}, $new_sids)));
				$v['field'] = isset($fsfields[$v['field_scene_id']]) ? (isset($fields[$fsfields[$v['field_scene_id']]]) ? "{$fields[$fsfields[$v['field_scene_id']]]['field']}" : '') : '';
				$v['scene_ids'] = array_map('intval', $new_sids);
				$v['choice_name'] = isset($fsfields[$v['field_scene_id']]) ? (isset($fields[$fsfields[$v['field_scene_id']]]['cid']) ? ($choices[$fields[$fsfields[$v['field_scene_id']]]['cid']] ?? '') : '') : '';
				$v['state_name'] = CsmsFieldScene::state[$v['state']] ?? '';
				$v['valid_state_name'] = CsmsFieldScene::state[$v['valid_state']] ?? '';
				$v['type_of_name'] = CsmsFieldScene::type[$v['type']] ?? '';
			}
		}
		return ['data' => $list, 'total' => $total];
	}

	/**
	 * @param array $condition
	 * @return int
	 */
	public function fieldSceneMachineTotal(array $condition)
	{
		unset($condition['offset']);
		unset($condition['limit']);
		unset($condition['orderBy']);
		return CsmsFieldSceneMachine::handleTotal($condition);
	}

	/**
	 * @param FieldSceneMachineContext $context
	 * @return array
	 */
	private function filterConditions(FieldSceneMachineContext $context): array
	{
		$condition = array(
			'limit' => $context->limit,
			'offset' => $context->offset,
			'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
			'field_scene_id' => $context->sfid,
		);
		return $this->filter($condition);
	}

	/**
	 * 新增
	 * @param FsMachineContext $context
	 * @return bool
	 */
	public function addFieldSceneMachine(FsMachineContext $context): bool
	{
		$one = CsmsFieldScene::handleOne(array(
			'id' => $context->fieldSceneId,
			'state' => CsmsFieldScene::STATE_NORMAL
		));
		if (!$one) {
			ServicerException::throwException(ServicerException::UNVALID_FIELD_SCENE);
		}
		$conditon = array(
			'field_scene_id' => $context->fieldSceneId,
			'scene_ids' => implode(',', $context->sceneIds),
			'valid_state' => $context->validState,
			'type' => $context->type,
			'sort' => $context->sort,
			'state' => $context->state,
		);
		$res = CsmsFieldSceneMachine::saveModel($conditon);
		if ($res) {
			// 记录日志
			CsmsOperateLog::saveModel(array(
				'type' => CsmsOperateLog::addMachine,
				'arm_id' => CsmsFieldScene::lastInsertId(),
				'admin' => $context->admin,
				'desc' => json_encode($conditon),
			));
		}
		return $res;
	}

	/**
	 * 更新
	 * @param FsMachineContext $context
	 * @return bool
	 */
	public function updateFieldSceneMachine(FsMachineContext $context): bool
	{
		$one = CsmsFieldSceneMachine::handleOne(array(
			'id' => $context->id
		));
		if (!$one) {
			ServicerException::throwException(ServicerException::UNVALID_FIELD_SCENE_ERROR);
		}
		$conditon = array(
			'field_scene_id' => $context->fieldSceneId,
			'scene_ids' => implode(',', $context->sceneIds),
			'valid_state' => $context->validState,
			'type' => $context->type,
			'sort' => $context->sort,
			'state' => $context->state,
		);
		$res = CsmsFieldSceneMachine::handleEdit($context->id, $conditon);
		if ($res) {
			// 记录日志
			CsmsOperateLog::saveModel(array(
				'type' => CsmsOperateLog::editMachine,
				'arm_id' => $context->id,
				'admin' => $context->admin,
				'desc' => json_encode($conditon),
			));
		}
		return $res;
	}

	/**
	 * 删除
	 * @param FeildSceneOperateContext $context
	 * @return bool
	 */
	public function deleteFieldScene(FeildSceneOperateContext $context)
	{
		$res = CsmsFieldScene::handleDelete($context->id);
		if ($res) {
			// 记录日志
			CsmsOperateLog::saveModel(array(
				'type' => CsmsOperateLog::deleteFeildScene,
				'arm_id' => $context->id,
				'admin' => $context->admin,
				'desc' => '{}',
			));
		}
		return $res;
	}

}
