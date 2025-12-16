<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\Chat;

use Imee\Models\Xs\XsBigarea;
use Imee\Service\Domain\Context\Cs\Statistics\Chat\ListContext;
use Imee\Models\Xsst\XsstChatStatistics;

use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;

/**
 * 客服满意度统计列表
 */
class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsstChatStatistics::class;
        $this->query = XsstChatStatistics::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        if (!empty($this->context->startTime)) {
            $where['condition'][] = 'dateline >= :start_time:';
            $where['bind']['start_time'] = strtotime($this->context->startTime);
        }

        if (!empty($this->context->endTime)) {
            $where['condition'][] = 'dateline < :end_time:';
            $where['bind']['end_time'] = strtotime($this->context->endTime) + 86400;
        }

		if (!empty($this->context->bigArea)) {
			$where['condition'][] = 'big_area = :big_area:';
			$where['bind']['big_area'] = $this->context->bigArea;
		}

        $this->where = $where;
    }

    protected function formatList($items)
    {
        $format = [];
        if (empty($items)) {
            return $format;
        }

        foreach ($items as $item) {
            $tmp = $item->toArray();

            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }

        foreach ($format as &$value) {
            $value['dateline'] = date("Y-m-d", $value['dateline']);
            $value['user_wait_time_avg'] = ($value['initiative_user_connect'] ? round($value['user_wait_time'] / $value['initiative_user_connect'], 2) : 0);
            $value['user_chat_time_avg'] = ($value['initiative_user_connect'] ? round($value['user_chat_time'] / $value['initiative_user_connect'], 2) : 0);
            $value['vote_join'] = (($value['total'] - $value['service_timeout'] - $value['service_noanswer']) ? round(($value['vote_yes']+$value['vote_no']) / ($value['total'] - $value['service_timeout'] - $value['service_noanswer']) * 100, 2) : 0);
            $value['vote_yes_percent'] = (($value['total'] - $value['service_timeout'] - $value['service_noanswer']) ? round($value['vote_yes'] / ($value['total'] - $value['service_timeout'] - $value['service_noanswer']) * 100, 2) : 0);

            $value['vote_manyi_percent'] = ($value['vote_yes'] + $value['vote_no']) ? round($value['vote_yes'] / ($value['vote_yes'] + $value['vote_no']) * 100, 2) : 0;
            $value['manyi'] = round($value['vote_join'] * $value['vote_manyi_percent'] / 100, 2);
            $value['no_manyi'] = ($value['vote_yes'] + $value['vote_no']) * $value['vote_join'] ?
                    round($value['vote_no'] / ($value['vote_yes'] + $value['vote_no']) * $value['vote_join'], 2) : 0;
            $value['user_finish_time_avg'] = $value['initiative_user_connect'] ? round($value['user_finish_time'] / $value['initiative_user_connect'], 2) : 0;
            $value['bia_area_name'] = XsBigarea::getBigAreaCnName($value['big_area']);
        }
        return $format;
    }
}
