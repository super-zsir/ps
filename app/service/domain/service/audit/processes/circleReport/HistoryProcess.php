<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;

use Imee\Models\Xs\XsCircleReport;
use Imee\Models\Xsst\XsstCircleVerifyLog;
use Imee\Service\Domain\Context\PageContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

class HistoryProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;

    public function __construct(PageContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsstCircleVerifyLog::class;
        $this->query = XsstCircleVerifyLog::query();
    }


    public function buildWhere()
    {
        // TODO: Implement buildWhere() method.
        $this->where['condition'][] = "$this->masterClass.type = ".XsstCircleVerifyLog::TYPE_CIRCLE_REPORT;

        $rpid = $this->context->rpid;
        if ($rpid) {
            $this->where['condition'][] = "$this->masterClass.relate_id = :rpid:";
            $this->where['bind']['rpid'] = intval($rpid);
        }
    }

    public function formatList($items)
    {
        // TODO: Implement formatList() method.
        if ($items) {
            $items = $items->toArray();
            $admin_uids = array_column($items, 'admin');
            $admins = $this->getStaffBaseInfos($admin_uids);
            foreach ($items as &$item) {
                $item['admin_name'] = isset($admins[$item['admin']]) ? $admins[$item['admin']]['user_name'] : '';
                $item['dateline'] = date('Y-m-d H:i:s', $item['dateline']);
                $item['operate_name'] = XsCircleReport::$status[$item['operate']];
            }
        }
        return $items;
    }
}
