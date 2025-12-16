<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChatLog;

use Imee\Exception\Cs\StatisticsException;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChatLog\AutoReplyListContext;
use Imee\Models\Xss\XssAutoService;
use Imee\Models\Xsst\XsstAutoQuestionLog;
use Imee\Service\Cachemanager\Cs\StatisticsAutoReplyNewListCache;
use Phalcon\Di;

/**
 * 自动回复结果统计
 */
class AutoReplyListProcess
{
    protected $context;
    private $serviceWhere = '';

    public function __construct(AutoReplyListContext $context)
    {
        $this->context = $context;
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        if (!empty($this->context->startTime)) {
            $where['condition'][] = 'dateline >= :start_time:';
            $where['bind']['start_time'] = strtotime($this->context->startTime);
            $this->serviceWhere .= " and service_start >= " . strtotime($this->context->startTime);
        } else {
            $where['condition'][] = 'dateline >= :start_time:';
            $where['bind']['start_time'] = strtotime('-7 day');
            $this->serviceWhere .= " and service_start >= " . strtotime('-7 day');
        }

        if (!empty($this->context->endTime)) {
            $where['condition'][] = 'dateline < :end_time:';
            $where['bind']['end_time'] = strtotime($this->context->endTime) + 86400;
            $this->serviceWhere .= " and service_start < " . (strtotime($this->context->endTime) + 86400);
        }

        if (!empty($this->context->startTime) && !empty($this->context->endTime)) {
            $diff = strtotime($this->context->endTime) - strtotime($this->context->startTime);
            if ($diff < 0 || $diff > 86400*30) {
                list($code, $msg) = StatisticsException::TIME_RANGE_ERROR;
                $msg = sprintf($msg, 30);
                throw new StatisticsException($msg, $code);
            }
        }

        if (!empty($this->context->language)) {
			$where['condition'][] = 'language = :language:';
			$where['bind']['language'] = $this->context->language;
			$this->serviceWhere .= " and language = '{$this->context->language}'";
		}

        return $where;
    }

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        $where = $this->buildWhere();

        $list = XsstAutoQuestionLog::find([
            'conditions' => implode(' and ', $where['condition']),
            'bind' => $where['bind'],
        ])->toArray();
    
        return $this->formatList($list);
    }

    private function formatList($list)
    {
        $format = [
            'total' => 0,
            'data' => [],
        ];
        if (empty($list)) {
            return $format;
        }

        //统计转人工用户ID
        $service_info = array();
        $all_uids = array_unique(array_column($list, 'uid'));
        $all_uids = array_chunk($all_uids, 10000);
        foreach ($all_uids as $uids) {
            $chatMessages = XssAutoService::find([
                'columns' => 'uid,service_start',
                'conditions' => 'uid IN ({uids:array})' . $this->serviceWhere,
                'bind' => ['uids' => $uids],
            ])->toArray();
            foreach ($chatMessages as $message) {
                $day = date('Y-m-d', $message['service_start']);
                if (!isset($service_info[$day][$message['uid']])) {
                    $service_info[$day][$message['uid']] = 1;
                } else {
                    $service_info[$day][$message['uid']] += 1;
                }
            }
        }

        $info = array();
        foreach ($list as $v) {
            $day = date('Y-m-d', $v['dateline']);
            $v['day'] = $day;
            $info[$day][] = $v;
        }
        unset($list);
        $staticField = array(
            'date' => 0,//日期
            'input_nums' => 0,//用户输入的次数
            'auto_reply_nums' => 0,//触发自动应答数
            'undistinguish_question_nums' => 0,//未识别问题次数
            'servers_nums' => 0,//直接找人工次数
            'convert_servers_nums' => 0,//转人工次数
            'convert_servers_rate' => 0,//转人工率
            'vote_nums' => 0,//主动评价次数
            'vote_useful_nums' => 0,//主动有用次数
            'vote_useless_nums' => 0,//主动无用次数
        );
        $result = array();
        $index = 0;
        foreach ($info as $key => $item) {
            $result[$index] = $staticField;
            foreach ($item as $v) {
                $result[$index]['date'] = $v['day'];
                $result[$index]['input_nums'] += 1;
                $result[$index]['auto_reply_nums'] += ($v['type'] == 1 || $v['type'] == 3) ? 1 : 0;
                $result[$index]['undistinguish_question_nums'] += $v['type'] == 2 ? 1 : 0;
                $result[$index]['servers_nums'] += $v['is_service'];
                if (isset($service_info[$key][$v['uid']]) && $service_info[$key][$v['uid']] > 0) {
                    $result[$index]['convert_servers_nums'] += $service_info[$key][$v['uid']];
                    $service_info[$key][$v['uid']] = 0;//避免同uid重复相加
                }
                $result[$index]['vote_nums'] += $v['vote_type'] > -1 ? 1 : 0;
                $result[$index]['vote_useful_nums'] += $v['vote_type'] == 1 ? 1 : 0;
                $result[$index]['vote_useless_nums'] += $v['vote_type'] == 0 ? 1 : 0;
            }

            if ($result[$index]['undistinguish_question_nums']>0) {
                $result[$index]['convert_servers_rate'] = sprintf('%.2f%%', 100*$result[$index]['convert_servers_nums']/$result[$index]['input_nums']);
            }
            $result[$index]['auto_reply_rate'] = $result[$index]['input_nums'] == 0
                ? 0 : sprintf('%.2f%%', 100 * $result[$index]['auto_reply_nums']/$result[$index]['input_nums']);
            $result[$index]['undistinguish_question_rate'] = $result[$index]['input_nums'] == 0
                ? 0 : sprintf('%.2f%%', 100 * $result[$index]['undistinguish_question_nums']/$result[$index]['input_nums']);
            $index++;
        }

        //导出的时候使用
        $session = Di::getDefault()->getShared('session');
        
        $adminUid = $session->get('uid');
        $cache = new StatisticsAutoReplyNewListCache;
        $cache->setex($adminUid, $cache->getExpireTime(), serialize($result));
        return [
            'data' => $result,
            'total' => count($result),
        ];
    }
}
