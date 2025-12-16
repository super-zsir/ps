<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsFieldScene;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsServicer;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\FeildSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ServicerException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 审核字段场景
 */
class FieldSceneProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 审核项列表
     * @param FeildSceneContext $context
     * @return array
     */
    public function fieldSceneList(FeildSceneContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'fid', 'sid', 'create_time', 'state'];
        $list = CsmsFieldScene::handleList($condition);
        $total = $this->fieldSceneTotal($condition);
        if ($list) {
            // 服务商场景信息
            $sids = array_column($list, 'sid');
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
            $fids = array_column($list, 'fid');
            $fields = CsmsChoiceField::handleList([
//                'columns' => ['id', 'field', 'db_name', 'table_name'],
                'id_array' => $fids
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
                $new_sids = explode(',', $v['sid']);
                $v['servicer'] = implode(',', array_unique(array_filter(array_map(function ($item) use ($servicers, $scene_list) {
                    return isset($servicers[$scene_list[$item]['sid']]) ? $servicers[$scene_list[$item]['sid']] : '';
                }, $new_sids))));
                $v['scene'] = implode(',', array_filter(array_map(function ($item) use ($scene_list) {
                    return isset($scene_list[$item]) ? $scene_list[$item]['name'] : '';
                }, $new_sids)));
                $v['field'] = isset($fields[$v['fid']]) ? "{$fields[$v['fid']]['field']}" : '';
                $v['sid'] = array_map('intval', $new_sids);
                $v['choice_name'] = isset($fields[$v['fid']]['cid']) ? ($choices[$fields[$v['fid']]['cid']] ?? '') : '';
                $v['state_name'] = CsmsFieldScene::state[$v['state']] ?? '';
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function fieldSceneTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsFieldScene::handleTotal($condition);
    }

    /**
     * @param FeildSceneContext $context
     * @return array
     */
    private function filterConditions(FeildSceneContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
            'fid' => $context->fid,
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param FeildSceneOperateContext $context
     * @return bool
     */
    public function addFieldScene(FeildSceneOperateContext $context): bool
    {
        $one = CsmsFieldScene::handleOne(array(
            'fid' => $context->fid
        ));
        if ($one) {
            ServicerException::throwException(ServicerException::FIELD_HAVE_SCENE);
        }
        $conditon = array(
            'fid' => $context->fid,
            'sid' => implode(',', $context->sid),
            'state' => $context->state,
        );
        $res = CsmsFieldScene::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addFeildScene,
                'arm_id' => CsmsFieldScene::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param FeildSceneOperateContext $context
     * @return bool
     */
    public function updateFieldScene(FeildSceneOperateContext $context): bool
    {
        $one = CsmsFieldScene::handleOne(array(
            'fid' => $context->fid
        ));
        if ($one && $one->id != $context->id) {
            ServicerException::throwException(ServicerException::FIELD_HAVE_SCENE);
        }
        $conditon = array(
            'sid' => implode(',', $context->sid),
            'fid' => $context->fid,
            'state' => $context->state,
        );
        $res = CsmsFieldScene::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editFeildScene,
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

	/**
	 * @return array
	 */
	public function fieldScenes()
	{
		$list = CsmsFieldScene::handleList(array(
			'columns' => ['fid', 'id', 'sid'],
			'state' => CsmsServicer::STATE_NORMAL
		));
		$fids = array_column($list, 'fid');
		$sids = array_column($list, 'sid');
		// 字段信息
		$fields = CsmsChoiceField::handleList(array(
			'columns' => ['choice','field','id'],
			'id_array' => $fids
		));
		$fields = array_column($fields, null, 'id');
		// 场景信息
		$scenes = CsmsServicerScene::handleList(array(
			'columns' => ['name','mark','id'],
			'id_array' => $fids
		));
		$scenes = array_column($scenes, null, 'id');

		$res = [];
		if ($list) {
			foreach ($list as $item) {
				$sname = $scenes[$item['sid']]['name'] ?? '';
				$smark = $scenes[$item['sid']]['mark'] ?? '';
				$choice = $fields[$item['fid']]['choice'] ?? '';
				$field = $fields[$item['fid']]['field'] ?? '';
				$res[] = array(
					'label' => $choice.'-'.$field."({$smark}-{$sname})",
					'value' => $item['id'],
				);
			}
		}
		return $res;
	}
}
