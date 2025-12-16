<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\ChatTypeStat;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\XssChatTypeStat;
use Imee\Models\Xss\XssAutoService;
use Imee\Service\Domain\Context\Cs\Statistics\ChatTypeStat\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Helper\Constant\AutoChatConstant;

/**
 * 会话分类统计
 */
class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XssChatTypeStat::class;
        $this->query = XssChatTypeStat::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        if (!empty($this->context->start)) {
            $where['condition'][] = 'date_time >= :start:';
            $where['bind']['start'] = strtotime($this->context->start);
        }

        if (!empty($this->context->end)) {
            $where['condition'][] = 'date_time <= :end:';
            $where['bind']['end'] = strtotime($this->context->end);
        }

        if (is_numeric($this->context->service)) {
            $where['condition'][] = 'service = :service:';
            $where['bind']['service'] = $this->context->service;
        } else {
            $where['condition'][] = 'service > 0';
        }


        if (is_numeric($this->context->activeType)) {
            $where['condition'][] = 'active_type = :active_type:';
            $where['bind']['active_type'] = $this->context->activeType;
        } else {
            $where['condition'][] = 'active_type > 0';
        }

        // 2022-08-10 开始区分大区
        if (!empty($this->context->bigArea)) {
			$where['condition'][] = 'big_area = :big_area:';
			$where['bind']['big_area'] = $this->context->bigArea;
		}

        $this->where = $where;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $items = !is_array($items) ? $items->toArray() : $items;

        $total = [
            'date_time' => '合计',
            'service_name' => '-',
            'big_area_name' => '-',
            'total' => 0,
            'user_active' => 0,
            'service_active' => 0,
        ];
        foreach (AutoChatConstant::QUESTION_TYPE as $key => $value) {
            $total['type' . $key] = 0;
        }

        foreach ($items as &$item) {
            $data_json = json_decode($item['data_json'], true);
            if (empty($data_json)) {
                continue;
            }
            $item['date_time'] = date("Y-m-d", $item['date_time']);
            $item['service_name'] = XssAutoService::$manualChatServiceConfig[$item['service']] ?? '-';
            $item['big_area_name'] = XsBigarea::getBigAreaCnName($item['big_area']);
            if ($item['service'] == 0) {
                $item['service_name'] = '汇总';
            }
            foreach ($data_json as $k => $v) {
                $key = isset(AutoChatConstant::QUESTION_TYPE[$k]) ? 'type' . $k : $k;
                $item[$key] = $v;
                if (isset($total[$key])) {
                    $total[$key] += $v;
                }
            }
        }
        array_unshift($items, $total);

        return $items;
    }
}
