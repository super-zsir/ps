<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsServicer;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerSceneContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerSceneOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ServicerException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 服务商场景
 */
class ServicerSceneProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 服务商场景列表
     * @param ServicerSceneContext $context
     * @return array
     */
    public function servicerSceneList(ServicerSceneContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'sid', 'mark', 'name', 'description', 'state', 'create_time'];
        $list = CsmsServicerScene::handleList($condition);
        $total = $this->servicerSceneTotal($condition);
        if ($list) {
            $sids = array_column($list, 'sid');
            $servicers = CsmsServicer::handleList([
                'columns' => ['id', 'name'],
                'id_array' => $sids
            ]);
            $servicers = array_column($servicers, 'name', 'id');
            foreach ($list as &$v) {
                $v['state_name'] = CsmsServicerScene::state[$v['state']] ?? '';
                $v['servicer'] = $servicers[$v['sid']] ?? '';
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function servicerSceneTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsServicerScene::handleTotal($condition);
    }

    /**
     * @param ServicerSceneContext $context
     * @return array
     */
    private function filterConditions(ServicerSceneContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
            'sid' => $context->sid,
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param ServicerSceneOperateContext $context
     * @return bool
     */
    public function addServicerScene(ServicerSceneOperateContext $context): bool
    {
        $server = CsmsServicer::handleOne(array(
            'id' => $context->sid
        ));
        if (!$server) {
            ServicerException::throwException(ServicerException::SERVICER_NOT_EXIST);
        }
        $conditon = array(
            'sid' => $context->sid,
            'mark' => $context->mark,
            'name' => $context->name,
            'description' => $context->description,
            'state' => $context->state
        );
        $res = CsmsServicerScene::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addServicerScene,
                'arm_id' => CsmsServicerScene::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param ServicerSceneOperateContext $context
     * @return bool
     */
    public function updateServicerScene(ServicerSceneOperateContext $context): bool
    {
        $conditon = array(
            'sid' => $context->sid,
            'mark' => $context->mark,
            'name' => $context->name,
            'description' => $context->description,
            'state' => $context->state,
        );
        $res = CsmsServicerScene::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editServicerScene,
                'arm_id' => $context->id,
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * @return array
     */
    public function scenes()
    {
        $list = CsmsServicerScene::handleList(array(
            'columns' => ['name', 'id', 'mark'],
            'state' => CsmsServicer::STATE_NORMAL
        ));
        $res = [];
        if ($list) {
            foreach ($list as $item) {
                $res[] = array(
                    'label' => $item['name']."({$item['mark']})",
                    'value' => $item['id'],
                );
            }
        }
        return $res;
    }
}
