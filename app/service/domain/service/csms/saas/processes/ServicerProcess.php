<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsServicer;
use Imee\Models\Xss\CsmsServicerScene;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\ServicerOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\ServicerException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 服务商
 */
class ServicerProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 服务商列表
     * @param ServicerListContext $context
     * @return array
     */
    public function servicerList(ServicerListContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'mark', 'name', 'type', 'state', 'create_time'];
        $list = CsmsServicer::handleList($condition);
        $total = $this->servicerTotal($condition);
        if ($list) {
            foreach ($list as &$v) {
                $v['state_name'] = CsmsServicer::state[$v['state']] ?? '';
                $v['type_name'] = CsmsConstant::$csms_type[$v['type']];
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function servicerTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsServicer::handleTotal($condition);
    }

    /**
     * @param ServicerListContext $context
     * @return array
     */
    private function filterConditions(ServicerListContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' => empty($context->sort) ? '' : "{$context->sort} {$context->dir}"
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param ServicerOperateContext $context
     * @return bool
     */
    public function addServicer(ServicerOperateContext $context): bool
    {
        $conditon = array(
            'mark' => $context->mark,
            'name' => $context->name,
            'type' => $context->type,
	        'state' => $context->state
        );
        $res = CsmsServicer::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addServicer,
                'arm_id' => CsmsServicer::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param ServicerOperateContext $context
     * @return bool
     */
    public function updateServicer(ServicerOperateContext $context): bool
    {
        $server = CsmsServicer::handleOne(array(
            'mark' => $context->mark,
        ));
        if ($server && $server->id != $context->id) {
            ServicerException::throwException(ServicerException::SERVICER_ALREADY_EXIST);
        }
        // 下线检测
        if ($context->state == CsmsServicer::STATE_CANCEL) {
            $scene = CsmsServicerScene::handleOne(array(
                'sid' => $context->id,
                'state' => CsmsServicerScene::STATE_NORMAL,
                'alias' => 'CsmsServicerScene',
            ));
            if ($scene) {
                ServicerException::throwException(ServicerException::SERVICER_HAVE_STAGE);
            }
        }
        $conditon = array(
            'mark' => $context->mark,
            'name' => $context->name,
            'type' => $context->type,
            'state' => $context->state
        );
        $res = CsmsServicer::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editServicer,
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
    public function servicers()
    {
        $list = CsmsServicer::handleList(array(
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