<?php

namespace Imee\Service\Domain\Service\Audit\Processes\SensitiveWords;

use Imee\Models\Xss\XssSensitiveWordsRecord;
use Imee\Service\Domain\Context\Audit\SensitiveWords\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;

class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XssSensitiveWordsRecord::class;
        $this->query = XssSensitiveWordsRecord::query();
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

        if (!empty($this->context->uid)) {
            $where['condition'][] = 'uid = :uid:';
            $where['bind']['uid'] = $this->context->uid;
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

            $tmp['dateline'] = date("Y-m-d H:i:s", $tmp['dateline']);
            $format[] = $tmp;
        }

        return $format;
    }
}
