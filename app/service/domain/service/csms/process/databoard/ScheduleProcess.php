<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Bms\BmsAuditTurnout;
use Imee\Service\Domain\Service\Csms\Context\Databoard\ScheduleContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 考勤
 */
class ScheduleProcess extends NormalListAbstract
{
    /**
     * @var ScheduleContext
     */
    protected $context;

    protected $masterClass = BmsAuditTurnout::class;

    protected $query;

    public function __construct(ScheduleContext $context)
    {
        parent::__construct($context);

        $this->query = $this->masterClass::query();
    }

    protected function buildWhere()
    {
        if (!empty($this->context->dateline)) {
            $this->where['condition'][]      = 'dateline = :dateline:';
            $this->where['bind']['dateline'] = strtotime($this->context->dateline);
        } else {
            $this->where['condition'][] = 'dateline = '.strtotime(date('Y-m-01', time()));
        }
    }

    protected function formatList($items)
    {
        $format = [];
        $mopUids = [];
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $tmp['display_dateline'] = $tmp['dateline'] > 0 ? date('Y年m月', $tmp['dateline']) : '';
            $tmp['dateline'] = $tmp['dateline'] > 0 ? date('Y-m', $tmp['dateline']) : '';

            $format[] = $tmp;
            $mopUids[] = $item->admin;
        }

        if (empty($format)) {
            return $format;
        }

        $opInfoMap = CmsUser::getAdminUserBatch(array_values(array_unique($mopUids)));
        foreach ($format as &$v) {
            $v['admin_name'] = isset($opInfoMap[$v['admin']]) ? $opInfoMap[$v['admin']]['user_name'] : '';
        }

        return $format;
    }
}
