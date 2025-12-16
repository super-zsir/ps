<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceField;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsFieldScene;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditFeildListContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditFeildOperateContext;
use Imee\Service\Domain\Service\Csms\Exception\Saas\AuditDbException;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 审核项字段
 */
class AuditFieldProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 审核项列表
     * @param AuditFeildListContext $context
     * @return array
     */
    public function auditFeildList(AuditFeildListContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'cid', 'choice', 'field', 'type', 'depend_field', 'sort', 'state', 'admin', 'create_time'];
        $list = CsmsChoiceField::handleList($condition);
        $total = $this->auditFeildTotal($condition);
        if ($list) {
            // 操作人
            $adminIds = array_column($list, 'admin');

            $admins = $this->getStaffBaseInfos($adminIds);
            // 审核项信息
            $cids = array_column($list, 'cid');
            $choices = CsmsChoice::handleList(array(
                'id_array' => $cids,
                'columns' => ['id', 'choice_name'],
            ));
            $choices = array_column($choices, 'choice_name', 'id');
            foreach ($list as &$v) {
                $v['choice_name'] = $choices[$v['cid']] ?? '';
                $v['state_name'] = CsmsChoiceField::state[$v['state']] ?? '';
                $v['type_name'] = CsmsConstant::$csms_type[$v['type']];
                $v['operator'] = isset($admins[$v['admin']]) ? $admins[$v['admin']]['user_name'] : '';
                $v['sort'] = strval($v['sort']);
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function auditFeildTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsChoiceField::handleTotal($condition);
    }

    /**
     * @param AuditFeildListContext $context
     * @return array
     */
    private function filterConditions(AuditFeildListContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
            'cid' => $context->cid,
            'type' => $context->type,
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param AuditFeildOperateContext $context
     * @return bool
     */
    public function addAuditFeild(AuditFeildOperateContext $context): bool
    {
        $one = CsmsChoiceField::handleOne(array(
            'cid' => $context->cid,
            'field' => $context->field,
            'type' => $context->type
        ));
        if ($one) {
            AuditDbException::throwException(AuditDbException::AUDIT_FIELD_ALREADY_ADD);
        }
        $choice = CsmsChoice::handleOne(array(
            'id' => $context->cid
        ));
        $conditon = array(
            'cid' => $context->cid,
            'choice' => $choice->choice ?? '',
            'field' => $context->field,
            'type' => $context->type,
            'depend_field' => $context->dependField,
            'sort' => $context->sort,
            'state' => is_numeric($context->state) ? $context->state : 0,
//            'db_name' => $context->dbName,
//            'table_name' => $context->tableName,
//            'pk_field' => $context->pkField,
//            'uid_field' => $context->uidField,
//            'joinup' => $context->joinup,
//            'ignore_write' => $context->ignoreWrite,
//            'ignore_update' => $context->ignoreUpdate,
            'admin' => $context->admin,
        );
        $res = CsmsChoiceField::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editAuditField, // 新增
                'arm_id' => CsmsChoiceField::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        return $res;
    }

    /**
     * 更新
     * @param AuditFeildOperateContext $context
     * @return bool
     */
    public function updateAuditFeild(AuditFeildOperateContext $context): bool
    {
        // 字段不可更改为已有
        $one = CsmsChoiceField::handleOne(array(
            'cid' => $context->cid,
            'field' => $context->field,
        ));
        if ($one && $one->id != $context->id) {
            AuditDbException::throwException(AuditDbException::AUDIT_FIELD_ALREADY_ADD);
        }
        // 下线前校验 @todo 等配置生效改
//        if ($context->state == CsmsChoiceField::STATE_OFF) {
//            CsmsFieldScene::handleOne(array(
//                'fid' => $context->id,
//            ));
//        }

        $choice = CsmsChoice::handleOne(array(
            'id' => $context->cid
        ));
        $conditon = array(
            'cid' => $context->cid,
            'choice' => $choice->choice ?? '',
            'field' => $context->field,
            'type' => $context->type,
            'depend_field' => $context->dependField,
            'sort' => $context->sort,
//            'db_name' => $context->dbName,
//            'table_name' => $context->tableName,
//            'pk_field' => $context->pkField,
//            'uid_field' => $context->uidField,
//            'joinup' => $context->joinup,
//            'ignore_write' => $context->ignoreWrite,
//            'ignore_update' => $context->ignoreUpdate,
            'admin' => $context->admin,
            'state' => $context->state,
        );
        $res = CsmsChoiceField::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addAuditField, // 新增
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
    public function fields()
    {
        $list = CsmsChoiceField::handleList(array(
            'state' => CsmsChoiceField::STATE_NORMAL,
            'leftChoice' => 1,
            'alias' => 'ccf',
            'columns' => ['ccf.field', 'ccf.choice', 'ccf.type', 'cc.choice_name', 'ccf.id']
        ));
        $res = [];
        if ($list) {
            foreach ($list as $item) {
                $res[] = array(
                    'label' => "{$item['field']}(审核项：{$item['choice_name']} - 类型：{$item['type']})",
                    'value' => $item['id'],
                );
            }
        }
        return $res;
    }
}
