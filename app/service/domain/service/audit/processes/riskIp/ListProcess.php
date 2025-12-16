<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskIp;

use Imee\Models\Config\BbcRiskIpList;
use Imee\Service\Domain\Context\Audit\RiskIp\ListContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 风险IP列表
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
        $this->masterClass = BbcRiskIpList::class;
        $this->query = BbcRiskIpList::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

		$where['condition'][] = 'is_delete = 0';

		if (!empty($this->context->ip)) {
			$where['condition'][] = "ip like '{$this->context->ip}%'";
		}

        $this->where = $where;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $res = $items->toArray();

        foreach ($res as &$v) {
            $v['op_dateline'] = date('Y-m-d H:i:s', $v['op_dateline']);
        }

        return $res;
    }
}
