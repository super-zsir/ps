<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat;

use Imee\Exception\Cs\StatisticsException;
use Imee\Models\Xsst\XsstAutoQuestionLog;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChat\DetailContext;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 自动回复数据统计详情
 */
class DetailProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(DetailContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsstAutoQuestionLog::class;
        $this->query = XsstAutoQuestionLog::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        $type = intval($this->context->type);
        $qid = intval($this->context->qid);
        $qids = [];
        if ($type > 0) {
            $qids = XssAutoQuestion::findQidsByType($type);
        } elseif ($qid > 0) {
            $qids = [$qid];
        }
        if (empty($qids)) {
            list($code, $msg) = StatisticsException::AUTO_CHAT_DETAILS_ERROR;
            $msg = sprintf($msg, $code);
            throw new StatisticsException($msg, $code);
        }
        $where['condition'][] = 'qid in({qid:array})';
        $where['bind']['qid'] = $qids;

        if (!empty($this->context->startTs)) {
            $where['condition'][] = 'dateline >= :start_ts:';
            $where['bind']['start_ts'] = $this->context->startTs;
        }

        if (!empty($this->context->endTs)) {
            $where['condition'][] = 'dateline < :end_ts:';
            $where['bind']['end_ts'] = $this->context->endTs;
        }

        $this->where = $where;
    }

    protected function formatList($items)
    {
        $format = [];
        if (empty($items)) {
            return $format;
        }
        $format = $items->toArray();
        $question_types = XssAutoQuestion::findQuestionTypes();
        foreach ($format as &$v) {
            $v['dateline'] = date('Y-m-d H:i:s', $v['dateline']);
            $type = $question_types[$v['qid']] ?? 0;
            $v['type'] = XssAutoQuestion::$QUESTION_TYPE[$type] ?? '-';
            $v['vote_type'] = XsstAutoQuestionLog::$displayVoteType[$v['vote_type']] ?? '-';
        }

        return $format;
    }
}
