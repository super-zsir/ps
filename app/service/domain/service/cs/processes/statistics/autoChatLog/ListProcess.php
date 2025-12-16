<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog;

use Imee\Models\Xs\XsBigarea;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog\ListContext;
use Imee\Models\Xsst\XsstAutoQuestionLog;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;
use Imee\Service\Helper;

/**
 * 自动应答统计列表
 */
class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XsstAutoQuestionLog::class;
        $this->query = XsstAutoQuestionLog::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

		$where['condition'][] = 'app_id = :app_id:';
		$where['bind']['app_id'] = APP_ID;

        if (!empty($this->context->startTime)) {
            $where['condition'][] = 'dateline >= :start_time:';
            $where['bind']['start_time'] = strtotime($this->context->startTime);
        }

        if (!empty($this->context->endTime)) {
            $where['condition'][] = 'dateline < :end_time:';
            $where['bind']['end_time'] = strtotime($this->context->endTime) + 86400;
        }

        if (!empty($this->context->type)) {
            $where['condition'][] = 'type = :type:';
            $where['bind']['type'] = $this->context->type;
        }

        if (!is_null($this->context->isService) && $this->context->isService != '') {
            $where['condition'][] = 'is_service = :is_service:';
            $where['bind']['is_service'] = $this->context->isService;
        }

        if (!empty($this->context->uid)) {
            $where['condition'][] = 'uid = :uid:';
            $where['bind']['uid'] = $this->context->uid;
        }

        if (!empty($this->context->tag)) {
            $questions = XssAutoQuestion::findQuestions($this->context->tag);
            
            if (empty($questions)) {
                $qids = [-1];
            } else {
                $qids = array_column($questions, 'id');
            }
            $where['condition'][] = 'qid in({qids:array})';
            $where['bind']['qids'] = $qids;
        }

        if (!empty($this->context->content)) {
            $where['condition'][] = 'content like :content:';
            $where['bind']['content'] = '%' . $this->context->content . '%';
        }

        if (!empty($this->context->reply)) {
            $where['condition'][] = 'reply like :reply:';
            $where['bind']['reply'] = '%' . $this->context->reply . '%';
        }

		if (!empty($this->context->language)) {
			$where['condition'][] = 'language = :language:';
			$where['bind']['language'] = $this->context->language;
		}

        $this->where = $where;
    }

    protected function formatList($items)
    {
        $format = [];
        if (empty($items)) {
            return $format;
        }
        $qids = [];
        foreach ($items as $item) {
            $qids[] = $item->qid;
            $tmp = $item->toArray();
            
            $tmp['dateline'] = date("Y-m-d H:i:s", $tmp['dateline']);
            $tmp['vote_time'] = $tmp['vote_time'] > 0 ? date('Y-m-d H:i', $tmp['vote_time']): '-';
            $tmp['display_is_service'] = XsstAutoQuestionLog::$displayIsService[$tmp['is_service']];
            $tmp['display_type'] = XsstAutoQuestionLog::$displayType[$tmp['type']];
            $tmp['display_vote_type'] = isset(XsstAutoQuestionLog::$displayVoteType[$tmp['vote_type']]) ?
                XsstAutoQuestionLog::$displayVoteType[$tmp['vote_type']] : '';
			$tmp['language_name'] = XsBigarea::getBigAreaCnName($tmp['language']);
            
            $format[] = $tmp;
        }

        if (empty($format)) {
            return $format;
        }
        $questionMap = [];
        $questionList = XssAutoQuestion::find([
            'conditions' => 'id in({ids:array})',
            'bind' => [
                'ids' => $qids,
            ],
        ])->toArray();
        if (!empty($questionList)) {
            $questionMap = array_column($questionList, null, 'id');
        }
        foreach ($format as &$v) {
            $v['qname'] = isset($questionMap[$v['qid']]) ? $questionMap[$v['qid']]['subject'] : '';
        }
        return $format;
    }
}
