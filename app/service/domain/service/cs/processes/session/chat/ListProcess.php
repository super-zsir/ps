<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Session\Chat;

use Imee\Models\Xs\XsUserTitleConfigNew;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Models\Xss\XssAutoService;
use Imee\Service\Domain\Context\Cs\Session\Chat\ListContext;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 会话管理列表
 */
class ListProcess extends NormalListAbstract
{
    use UserInfoTrait;

    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->masterClass = XssAutoService::class;
        $this->query = XssAutoService::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

		$where['condition'][] = 'app_id = :app_id:';
		$where['bind']['app_id'] = APP_ID;

        if (!empty($this->context->service)) {
            $where['condition'][] = 'service = :service:';
            $where['bind']['service'] = $this->context->service;
        }

        if (!empty($this->context->uid)) {
            $where['condition'][] = 'uid = :uid:';
            $where['bind']['uid'] = $this->context->uid;
        }

        if (!empty($this->context->serviceUid)) {
            $where['condition'][] = 'service_uid = :service_uid:';
            $where['bind']['service_uid'] = $this->context->serviceUid;
        }

        if (!empty($this->context->reason)) {
            $where['condition'][] = 'reason = :reason:';
            $where['bind']['reason'] = $this->context->reason;
        }

        if (!empty($this->context->vote)) {
            $where['condition'][] = 'vote = :vote:';
            $where['bind']['vote'] = $this->context->vote;
        }

		if (!empty($this->context->language)) {
			$where['condition'][] = 'language = :language:';
			$where['bind']['language'] = $this->context->language;
		}

        if (!empty($this->context->chatType)) {
            $where['condition'][] = 'chat_type = :chat_type:';
            $where['bind']['chat_type'] = $this->context->chatType;
        }

        if (!empty($this->context->start)) {
            $where['condition'][] = 'service_start >= :start:';
            $where['bind']['start'] = strtotime($this->context->start);
        }

        if (!empty($this->context->end)) {
            $where['condition'][] = 'service_start < :end:';
            $where['bind']['end'] = strtotime($this->context->end) + 86400;
        }

        $this->where = $where;
    }

    protected function formatList($items)
    {
        if (empty($items)) {
            return [];
        }
        $res = $items->toArray();

		$titleMap = XsUserTitleConfigNew::getAllTitleName();

        foreach ($res as &$val) {
            //超时会话，需减去会话超时时间
            $last_session_dateline = (!in_array($val['reason'], ['ok','user_no_reply','no_answer']) &&
                intval($val['end_time']) > 0) ? intval($val['end_time']) - 1800 : intval($val['end_time']);
            $vote_dateline = intval($val['vote_time']) - intval($val['end_time']) > 0 ?
                intval($val['vote_time']) - intval($val['end_time']) : 0;
            $val['user_start_string'] = date('Y-m-d H:i:s', intval($val['user_start']));
            $val['end_time'] = intval($val['end_time']) > 0 ? date('Y-m-d H:i:s', intval($val['end_time'])) : '';
            $val['vote_time'] = intval($val['vote_time']) > 0 ? date('Y-m-d H:i:s', intval($val['vote_time'])) : '';
            $val['service_name'] = XssAutoService::$serviceArray[$val['service']] ?? ' - ';
            $val['app_name'] = Helper::getAppName($val['app_id']);
            $val['last_session_time'] = $last_session_dateline > 0 ? date('Y-m-d H:i:s', $last_session_dateline) : '';
            $val['first_service_cost'] = intval($val['reply_start']) - intval($val['service_start']) > 0 ?
                intval($val['reply_start']) - intval($val['service_start']) : 0;
            $val['service_cost'] = $last_session_dateline - intval($val['service_start']) > 0 ?
                $last_session_dateline - intval($val['service_start']) : 0;
            $val['vote_in_thirty_min'] = ($vote_dateline > 0 && $vote_dateline < 1800) ? '是' : '否';
            $val['chat_type_name'] = XssAutoQuestion::$QUESTION_TYPE[$val['chat_type']] ?? '-';
            $val['reason_name'] = XssAutoService::$reasonArray[$val['reason']] ?? '-';
            $val['reply_cost'] = intval($val['reply_start']) > 0 ?
                intval($val['reply_start']) - intval($val['user_start']) : 0;
            $val['session_source'] = $val['reply_start'] == $val['service_start'] ? '客服' : '用户';
            $val['vote'] = in_array($val['vote'], ['yes', 'no']) ? XssAutoService::$voteArray[$val['vote']] : '-';
            $val['title_name'] = $titleMap[$val['title']] ?? '-';
        }

        return $res;
    }
}
