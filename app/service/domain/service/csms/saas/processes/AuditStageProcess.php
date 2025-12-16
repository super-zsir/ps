<?php

namespace Imee\Service\Domain\Service\Csms\Saas\Processes;

use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Exception\Saas\AuditDbException;
use Imee\Helper\Constant\CsmsConstant;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsChoiceStage;
use Imee\Models\Xss\CsmsModules;
use Imee\Models\Xss\CsmsModulesChoice;
use Imee\Models\Xss\CsmsOperateLog;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditStageContext;
use Imee\Service\Domain\Service\Csms\Context\Saas\AuditStageOperateContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 审核项字段
 */
class AuditStageProcess
{
    use CsmsTrait;
    use UserInfoTrait;

    /**
     * 审核项列表
     * @param AuditStageContext $context
     * @return array
     */
    public function auditStageList(AuditStageContext $context): array
    {
        $condition = $this->filterConditions($context);
        $condition['columns'] = ['id', 'cid', 'choice', 'stage', 'inspect', 'review', 'info', 'state', 'dateline', 'admin'];
        $list = CsmsChoiceStage::handleList($condition);
        $total = $this->auditStageTotal($condition);
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
                $v['state_name'] = CsmsChoiceStage::state[$v['state']] ?? '';

                $v['stage_name'] = $this->getModuleName($v['stage']);

                $v['operator'] = isset($admins[$v['admin']]) ? $admins[$v['admin']]['user_name'] : '';
//                $v['inspect_name'] = $v['inspect'] > 0 ? $v['inspect'] . '%' : '100%';
                $v['inspect_name'] = $v['inspect'].'%';

                $v['dateline'] = $v['dateline'] > 0 ? date('Y-m-d H:i:s', $v['dateline']) : '';
            }
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param array $condition
     * @return int
     */
    public function auditStageTotal(array $condition)
    {
        unset($condition['offset']);
        unset($condition['limit']);
        unset($condition['orderBy']);
        return CsmsChoiceStage::handleTotal($condition);
    }

    /**
     * @param AuditStageContext $context
     * @return array
     */
    private function filterConditions(AuditStageContext $context): array
    {
        $condition = array(
            'limit' => $context->limit,
            'offset' => $context->offset,
            'orderBy' =>empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
            'cid' => $context->cid,
        );
        return $this->filter($condition);
    }

    /**
     * 新增
     * @param AuditStageOperateContext $context
     * @return bool
     */
    public function addAuditStage(AuditStageOperateContext $context): bool
    {
        // 审核项是否存在当前阶段
        $stage = $this->searchStage($context);
        if ($stage) {
            AuditDbException::throwException(AuditDbException::AUDIT_STAGE_ALREADY_ADD);
        }
        $choice = CsmsChoice::handleOne(array(
            'id' => $context->cid
        ));
        $conditon = array(
            'cid' => $context->cid,
            'choice' => $choice->choice ?? '',
            'stage' => $context->stage,
            'inspect' => $context->inspect,
            'review' => $context->review,
            'info' => $context->info,
            'dateline' => time(),
            'admin' => $context->admin,
        );
        $res = CsmsChoiceStage::saveModel($conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::addAuditStage, // 新增
                'arm_id' => CsmsChoiceStage::lastInsertId(),
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }

	    // 更新module_choice表
	    $csmsModule = CsmsModules::findFirst([
	    	'conditions' => 'module = :module:',
		    'bind' => [
		    	'module' => $context->stage
		    ]
	    ]);
	    $moduleChoice = CsmsModulesChoice::findFirst([
	    	'conditions' => 'mid = :mid: and choice = :choice:',
		    'bind' => [
		    	'mid' => $csmsModule->mid,
			    'choice' => $choice->choice,
		    ]
	    ]);
	    if(!$moduleChoice){
	    	$moduleChoice = new CsmsModulesChoice();
	    }
	    $moduleChoice->save([
	        'cid' => $context->cid,
			'mid' => $csmsModule->mid,
		    'choice' => $choice->choice,
		    'choice_name' => $choice->choice_name,
		    'state' => $context->state
	    ]);


        return true;
    }

    /**
     * 更新
     * @param AuditStageOperateContext $context
     * @return bool
     */
    public function updateAuditStage(AuditStageOperateContext $context): bool
    {
        // 当前审核项不可另外存在
        $stage = $this->searchStage($context);
        if ($stage && $stage->id != $context->id) {
            AuditDbException::throwException(AuditDbException::AUDIT_STAGE_ALREADY_ADD);
        }
        // 审核项不可没有
        $choice = CsmsChoice::handleOne(array(
            'id' => $context->cid
        ));
        if (!$choice) {
            AuditDbException::throwException(AuditDbException::AUDIT_NOT_EXIST);
        }
        // 阶段配置不可没有
        $csmsModule = CsmsModules::findFirst([
            'conditions' => 'module = :module:',
            'bind' => [
                'module' => $context->stage
            ]
        ]);
        if (!$csmsModule) {
            AuditDbException::throwException(AuditDbException::AUDIT_WITHOUT_SETTING);
        }
        // 当前阶段不可不存在
        $stage = CsmsChoiceStage::handleOne(array(
            'id' => $context->id,
        ));
        if (!$stage) {
            AuditDbException::throwException(AuditDbException::AUDIT_WITHOUT_STAGE);
        }
        $stageChoice = $stage->choice;
        $stageData = $stage->stage;
        // 当前阶段下如果有数据，不可更改 阶段标识和审核项
        if ($context->cid != $stage->cid || $context->stage != $stage->stage) {
            $audit = CsmsAudit::handleOne(array(
                'choice' => $stage->choice,
            ));
            if ($audit) {
                AuditDbException::throwException(AuditDbException::AUDIT_HAVE_DATA);
            }
        }
        // 更新
        $conditon = array(
            'cid' => $context->cid,
            'choice' => $choice->choice ?? '',
            'stage' => $context->stage,
            'inspect' => $context->inspect,
            'review' => $context->review,
            'info' => $context->info,
            'admin' => $context->admin,
            'state' => $context->state
        );
        // 编辑审核阶段
        $res = CsmsChoiceStage::handleEdit($context->id, $conditon);
        if ($res) {
            // 记录日志
            CsmsOperateLog::saveModel(array(
                'type' => CsmsOperateLog::editAuditStage, // 修改
                'arm_id' => $context->id,
                'admin' => $context->admin,
                'desc' => json_encode($conditon),
            ));
        }
        // 找出原始记录
        $csmsModuleNew = CsmsModules::findFirst([
            'conditions' => 'module = :module:',
            'bind' => [
                'module' => $stageData
            ]
        ]);
        // 查找审核模块记录
        $moduleChoice = CsmsModulesChoice::findFirst([
            'conditions' => 'mid = :mid: and choice = :choice:',
            'bind' => [
                'mid' => $csmsModuleNew->mid,
                'choice' => $stageChoice,
            ]
        ]);
        // 更新审核模块
        if ($moduleChoice) {
            $moduleChoice->state = $context->state;
            $moduleChoice->choice = $choice->choice ?? '';
            $moduleChoice->choice_name = $choice->choice_name ?? '';
            $moduleChoice->mid = $csmsModule->mid;
            $moduleChoice->cid = $context->cid;
            $moduleChoice->save();
        }
        return true;
    }

    /**
     * @param AuditStageOperateContext $context
     * @return mixed
     */
    public function searchStage(AuditStageOperateContext $context)
    {
        return CsmsChoiceStage::handleOne(array(
            'cid' => $context->cid,
            'stage' => $context->stage
        ));
    }
}
