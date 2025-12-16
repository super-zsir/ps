<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskUser;

use Imee\Comp\Operate\Auth\Service\StaffService;
use Imee\Helper\Constant\RiskConstant;
use Imee\Models\Lemon\UserActivenessLevel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserReaudit;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\XsUserReauditLog;
use Imee\Service\Domain\Context\Audit\RiskUser\ListContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 工单列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsUserReaudit::class;
        $this->query = XsUserReaudit::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

		if (!empty($this->context->start)) {
			$where['condition'][] = 'dateline >= :start:';
			$where['bind']['start'] = strtotime($this->context->start);
		}

		if (!empty($this->context->end)) {
			$where['condition'][] = 'dateline < :end:';
			$where['bind']['end'] = strtotime($this->context->end) + 86400;
		}

        if (!empty($this->context->status)) {
            $where['condition'][] = 'status = :status:';
            $where['bind']['status'] = $this->context->status;
        }

        if (!empty($this->context->uid)) {
            $where['condition'][] = 'uid = :uid:';
            $where['bind']['uid'] = $this->context->uid;
        }

        if (!empty($this->context->type)) {
            $where['condition'][] = 'type = :type:';
            $where['bind']['type'] = $this->context->type;
        }

		if (!empty($this->context->reason)) {
			$where['condition'][] = 'reason = :reason:';
			$where['bind']['reason'] = $this->context->reason;
		}

		if (!empty($this->context->language)) {
			$where['condition'][] = 'language = :language:';
			$where['bind']['language'] = $this->context->language;
		}

        $this->where = $where;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $res = $items->toArray();

        $uids = array_unique(array_column($res, 'uid'));
        $userInfoMap = $this->getUserInfoModel($uids)->vip()->language()->title()->registerIp()->handle();
        // 活跃等级
        $activeLevel = UserActivenessLevel::handleList(array(
            'uid_array' => array_values($uids),
            'columns' => ['uid', 'point'],
        ));
        $activeLevel = array_column($activeLevel, 'point', 'uid');
        $newActiveLevel = [];
        foreach ($uids as $uid) {
            $newActiveLevel[$uid] = isset($activeLevel[$uid]) ? UserActivenessLevel::getLevel($activeLevel[$uid]) : 0;
        }
        foreach ($res as &$v) {
            $v['active_level'] = $newActiveLevel[$v['uid']] ?? 0;
            $v['dateline'] = date('Y-m-d H:i:s', $v['dateline']);
            $v['icon'] = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['icon'] : '';
            $v['deleted'] = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['deleted'] : 0;
            $v['vip'] = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['vip'] : 0;
            $v['title_name'] = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['title_name'] : '';
            $sex = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['sex'] : 0;
            $v['sex'] = XsUserProfile::$sex_arr[$sex] ?? '';
            $v['deleted_name'] = XsUserProfile::$deleted_arr[$v['deleted']] ?? '';
            $v['status_name'] = XsUserReaudit::$status_arr[$v['status']] ?? '';
            $v['type_name'] = RiskConstant::RISK_USER_RULE_TYPES[$v['type']] ?? 'Rule ID:' . $v['type'];
            $v['last_op_uname'] = $this->getOpName($v['id']);
            $v['language_name'] = XsBigarea::langToBigAreaName($v['language']);
            $v['register_ip'] = isset($userInfoMap[$v['uid']]) ? $userInfoMap[$v['uid']]['register_ip'] : '';
        }

        return $res;
    }

    private function getOpName($rid)
	{
		if (empty($rid)) return '';

		$logModel = XsUserReauditLog::findFirst([
			'conditions' => 'rid=:rid:',
			'bind' => [
				'rid' => $rid,
			],
			'order' => 'id desc',
		]);
		if (!$logModel) {
			return '';
		}

		$staffService = new StaffService();
		$info = $staffService->getInfoByUid(intval($logModel->op_uid));
		return $info['user_name'] ?? '';
	}
}
