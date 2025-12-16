<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Setting\AutoReply;

use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Context\Cs\Setting\AutoReply\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;

/**
 * 工单列表
 */
class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XssAutoQuestion::class;
        $this->query = XssAutoQuestion::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

		$where['condition'][] = 'app_id = :app_id:';
		$where['bind']['app_id'] = APP_ID;

        if (!empty($this->context->tag)) {
            $where['condition'][] = "tag like '%{$this->context->tag}%'";
        }
        if (!empty($this->context->subject)) {
            $where['condition'][] = "subject like '%{$this->context->subject}%'";
        }
        if (!empty($this->context->answer)) {
            $where['condition'][] = "answer like '%{$this->context->answer}%'";
        }
        if (!empty($this->context->type)) {
            $where['condition'][] = 'type = :type:';
            $where['bind']['type'] = $this->context->type;
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
            $tmp['type_name'] = XssAutoQuestion::$QUESTION_TYPE[$tmp['type']] ?? '-';
            $tmp['guide_to_service_name'] = XssAutoQuestion::$guide_to_service[$tmp['guide_to_service']] ?? '-';
            $tmp['language_name'] = Helper::getLanguageName($tmp['language']);
            $format[] = $tmp;
        }

        return $format;
    }
}
