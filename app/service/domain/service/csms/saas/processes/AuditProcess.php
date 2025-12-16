<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Models\Xss\CsmsProduct;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditOperatorContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\AuditDbException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 审核项
 */
class AuditProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 审核项列表
     * @param AuditListContext $context
     * @return array
     */
    public function auditList(AuditListContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'choice', 'choice_name', 'type', 'product', 'state', 'dateline', 'admin', 'joinup'];
        $list = CsmsChoice::handleList($condition);
        $total = $this->auditTotal($condition);
        if ($list) {
            $products = CsmsProduct::handleList(array(
                'columns' => ['app_id', 'name']
            ));
            $products = array_column($products, 'name', 'app_id');
            // 操作人
            $adminIds = array_column($list, 'admin');

            $admins = $this->getStaffBaseInfos($adminIds);

            foreach ($list as &$v) {
                $product_array = explode(',', $v['product']);
                $v['product'] = $product_array;
                $v['product_name'] = implode(',', array_filter(array_map(function ($item) use ($products) {
                    return $products[$item] ?? '';
                }, $product_array)));
                $v['state_name'] = CsmsChoice::state[$v['state']] ?? '';
                $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
                $v['operator'] = isset($admins[$v['admin']]) ? $admins[$v['admin']]['user_name'] : '';
                $v['choice_type'] = CsmsChoice::$choice_type[$v['type']];
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function auditTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsChoice::handleTotal($condition);
    }

    /**
     * @param AuditListContext $context
     * @return array
     */
    private function filterConditions(AuditListContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}"
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param AuditOperatorContext $context
     * @return bool
     */
    public function addAudit(AuditOperatorContext $context): bool
    {
        $one = CsmsChoice::handleOne(array(
            'choice' => $context->choice,
        ));
        if ($one) {
            AuditDbException::throwException(AuditDbException::AUDIT_ALREADY_ADD);
        }
        $conditon = array(
            'choice' => $context->choice,
            'choice_name' => $context->choiceName,
            'type' => $context->type,
            'product' => $context->product ? implode(',', $context->product) : '',
            'dateline' => time(),
            'admin' => $context->admin,
            'joinup' => $context->joinup,
            'state' => $context->state,
        );
        $res = CsmsChoice::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addAudit, // 新增
                'arm_id' => CsmsChoice::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param AuditOperatorContext $context
     * @return bool
     */
    public function updateAudit(AuditOperatorContext $context): bool
    {
        // 审核项重复性校验
        $one = CsmsChoice::handleOne(array(
            'choice' => $context->choice,
        ));
        if (!$one) {
            // 已新建阶段不可修改审核项标识
            $ccs = CsmsChoiceStage::handleOne(array(
                'cid' => $context->id
            ));
            if ($ccs) {
                // 不可修改审核标识
                AuditDbException::throwException(AuditDbException::AUDIT_STAGE_NOT_DELETE);
            }
            // 已兴建字段不可修改审核项标识
            $ccf = CsmsChoiceField::handleOne(array(
                'cid' => $context->id
            ));
            if ($ccf) {
                // 不可修改审核标识
                AuditDbException::throwException(AuditDbException::AUDIT_STAGE_NOT_DELETE);
            }
        }
        if ($one && $one->id != $context->id) {
            AuditDbException::throwException(AuditDbException::AUDIT_ALREADY_ADD);
        }
        // 不可下线使用中的审核项
        if ($context->state == CsmsChoice::state_unnormal) {
            // 查找是否有使用中的字段
            $field = CsmsChoiceField::handleOne(array(
                'cid' => $context->id,
                'state' => CsmsChoiceField::STATE_NORMAL
            ));
            if ($field) {
                AuditDbException::throwException(AuditDbException::AUDIT_FIELD_ALREADY_EXIST);
            }
            // 查找是否有使用中的场景
            $stage = CsmsChoiceStage::handleOne(array(
                'cid' => $context->id,
                'state' => CsmsChoiceStage::STATE_NORMAL
            ));
            if ($stage) {
                AuditDbException::throwException(AuditDbException::AUDIT_STAGE_ALREADY_EXIST);
            }
        }
        $conditon = array(
            'choice' => $context->choice,
            'choice_name' => $context->choiceName,
            'type' => $context->type,
            'product' => $context->product ? implode(',', $context->product) : '',
            'joinup' => $context->joinup,
            'state' => $context->state,
            'admin' => $context->admin,
        );
        $res = CsmsChoice::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editAudit, // 更新
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
    public function getValidAudit()
    {
        $list = CsmsChoice::handleList(array(
            'state' => 1,
            'columns' => ['id', 'choice', 'choice_name'],
        ));
        $res = [];
        if ($list) {
            foreach ($list as $item) {
                $res[] = array(
                    'label' => $item['choice_name']."({$item['choice']})",
                    'value' => $item['id'],
                );
            }
        }
        return $res;
    }
}
