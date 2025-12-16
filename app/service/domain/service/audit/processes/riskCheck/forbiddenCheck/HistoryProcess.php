<?php
namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Models\Xsst\XsstUserForbiddenCheckLog;
use Imee\Models\Xsst\XsstUserForbiddenCheck;
use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\HistoryContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 历史列表
 */
class HistoryProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(HistoryContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsstUserForbiddenCheckLog::class;
        $this->query = XsstUserForbiddenCheckLog::query();
    }

    protected function buildWhere()
    {
        $this->where['condition'][] = 'log_id=:log_id:';
        $this->where['bind']['log_id'] = $this->context->logId;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $data = $items->toArray();

        $opIds = array_unique(array_column($data, 'op_id'));
        $opInfos = $this->getStaffBaseInfos($opIds);

        foreach ($data as &$val) {
            $val['dateline'] = date('Y-m-d H:i:s', $val['dateline']);
            $val['op_name'] = $opInfos[$val['op_id']]['user_name'] ?: '';
            $val['status_name'] = XsstUserForbiddenCheck::$status_arr[$val['status']] ?: '';
        }

        return $data;
    }
}
