<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\QuickReply;

use Imee\Models\Config\BbcCustomerServiceQuickReply;
use Imee\Models\Config\BbcCustomerServiceQuickReplyGroup;
use Imee\Service\Domain\Context\Cs\Setting\QuickReply\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

/**
 * 快捷回复列表
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
        $this->masterClass = BbcCustomerServiceQuickReply::class;
        $this->query = BbcCustomerServiceQuickReply::query();
    }

	protected function buildWhere()
    {
		$where = ['condition' => [], 'bind' => []];

		$where['condition'][] = "deleted = " . BbcCustomerServiceQuickReply::DELETED_NO;
		if (!empty($this->context->groupId)) {
			$where['condition'][] = 'group_id = :group_id:';
			$where['bind']['group_id'] = $this->context->groupId;
		}
		if (!empty($this->context->content)) {
			$where['condition'][] = "content like '%{$this->context->content}%'";
		}

		$this->where = $where;
    }

	protected function formatList($items)
    {
        $format = [];
        $opUids = [];
        if (empty($items)) {
            return $format;
        }

        foreach ($items as $item) {
            $tmp = $item->toArray();
            $format[] = $tmp;
            $opUids[] = $item->op_uid;
        }
        if (empty($format)) {
            return $format;
        }

		$cmsUser = $this->getStaffBaseInfos($opUids);
        $groupName = $this->getGroupName();

        foreach ($format as &$v) {
            $v['dateline'] = date("Y-m-d H:i:s", $v['dateline']);
            $v['op_uname'] = isset($cmsUser[$v['op_uid']]) ? $cmsUser[$v['op_uid']]['user_name'] : '';

            $appArr = explode(',', $v['app_str']);
            $v['app_names'] = '';
            if (!empty($appArr)) {
                $v['app_names'] = implode(',', array_map(function ($v) {
                    return Helper::getAppName($v);
                }, $appArr));
            }
			$v['group_name'] = $groupName[$v['group_id']] ?? '-';
        }
        return $format;
    }

	private function getGroupName()
	{
		$groupName = [];
		$groupList = BbcCustomerServiceQuickReplyGroup::getList();
		if ($groupList) {
			foreach ($groupList as $group) {
				$groupName[$group['id']] = $group['group_name'];
			}
		}
		return $groupName;
	}
}
