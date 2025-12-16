<?php
namespace Imee\Service\Domain\Service\Csms\Process\Databoard\Forbiddenrisk;

use Imee\Models\Bms\BmsForbiddenRiskWeekKanban;
use Imee\Service\Domain\Service\Csms\Context\Databoard\Forbiddenrisk\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;
use Imee\Exception\Audit\DataboardDbException;

/**
 *
 */
class WeekProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = BmsForbiddenRiskWeekKanban::class;
        $this->query = BmsForbiddenRiskWeekKanban::query();
    }

    protected function buildWhere()
    {
        if (is_numeric($this->context->appId)) {
            $this->where['condition'][] = "app_id = :app_id:";
            $this->where['bind']['app_id'] = $this->context->appId;
        }

        if (!empty($this->context->beginTime)) {
            $beginTime = strtotime($this->context->beginTime);
            if (date('N', $beginTime) != 1) {
                DataboardDbException::throwException(DataboardDbException::BEGIN_TIME_ERROR);
            }
            $this->where['condition'][] = "dateline >= :begin_time:";
            $this->where['bind']['begin_time'] = $beginTime;
        }

        if (!empty($this->context->endTime)) {
            $endTime = strtotime($this->context->endTime);
            if (date('N', $endTime) != 7) {
                DataboardDbException::throwException(DataboardDbException::END_TIME_ERROR);
            }
            $this->where['condition'][] = "dateline < :end_time:";
            $this->where['bind']['end_time'] = $endTime + 86400;
        }
    }

    private function displayApp($appId)
    {
        if ($appId == -2) {
            return 'All';
        } elseif ($appId == -1) {
            return '其他';
        }
        return Helper::getAppName($appId);
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $format = [];
        foreach ($items as $item) {
            $tmp = $item->toArray();
            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }

        foreach ($format as &$v) {
            $v['dateline'] = date('Y-m-d', $v['dateline']) . '/' . date('Y-m-d', $v['dateline'] + 6*86400);

            $v['app_name'] = $this->displayApp($v['app_id']);

            //业务封禁量占比
            $v['other_rate'] = $v['forbidden_count'] > 0 ? bcdiv($v['other_count'], $v['forbidden_count'], 4) * 100 . '%' : '0%';

            //风险用户封禁占比
            $v['risk_rate'] = $v['forbidden_count'] > 0 ? bcdiv($v['risk_count'], $v['forbidden_count'], 4) * 100 . '%' : '0%';

            //系统封禁量占比
            $v['sys_rate'] = $v['forbidden_count'] > 0 ? bcdiv($v['sys_count'], $v['forbidden_count'], 4) * 100 . '%' : '0%';

            //业务永久封禁量占比
            $v['forever_other_rate'] = $v['forever_count'] > 0 ? bcdiv($v['forever_other_count'], $v['forever_count'], 4) * 100 . '%' : '0%';

            //风险用户永久封禁量占比
            $v['forever_risk_rate'] = $v['forever_count'] > 0 ? bcdiv($v['forever_risk_count'], $v['forever_count'], 4) * 100 . '%' : '0%';

            //系统永久封禁量占比
            $v['forever_sys_rate'] = $v['forever_count'] > 0 ? bcdiv($v['forever_sys_count'], $v['forever_count'], 4) * 100 . '%' : '0%';

            //风险用户抓取正确率
            $v['risk_grab_rate'] = $v['risk_reaudit_count'] > 0 ? bcdiv($v['risk_count'], $v['risk_reaudit_count'], 4) * 100 . '%' : '0%';

            //系统封禁正确率
            $v['sys_accuracy_rate'] = $v['sys_count'] > 0 ? bcdiv($v['sys_count'] - $v['sys_unforbidden_count'], $v['sys_count'], 4) * 100 . '%' : '0%';
        }

        return $format;
    }
}
