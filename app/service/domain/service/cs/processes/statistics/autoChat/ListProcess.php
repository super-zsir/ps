<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\AutoChat;

use Imee\Exception\Cs\StatisticsException;
use Imee\Models\Xss\XssAutoQuestion;
use Imee\Models\Xsst\XsstAutoQuestionLog;
use Imee\Service\Domain\Context\Cs\Statistics\AutoChat\ListContext;

/**
 * 自动回复数据统计列表
 */
class ListProcess
{
    protected $context;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        $statistical_type = intval($this->context->statisticalType);
        $tag = trim($this->context->tag);
        $language = trim($this->context->language);
        $start = !empty($this->context->start) ? date('Y-m-d', strtotime($this->context->start))
            : date('Y-m-d', strtotime('-1 day'));
        $end = !empty($this->context->end) ? date('Y-m-d', strtotime($this->context->end)) : date('Y-m-d', time());

        $diff = strtotime($end) - strtotime($start);
        if ($diff < 0 || $diff > 86400*30) {
            list($code, $msg) = StatisticsException::TIME_RANGE_ERROR;
            $msg = sprintf($msg, 30);
            throw new StatisticsException($msg, $code);
        }

        $date = $start . ' 至 ' . ($end ?: '今');
        $start_ts = strtotime($start);
        $end_ts = strtotime($end) + 86400;

        $result = [];
        if ($statistical_type == 1) {
            $result = $this->autoChatTypeStatistics($start_ts, $end_ts, $date, $language);
        } elseif ($statistical_type == 2) {
            $result =  $this->autoChatQuestionStatistics($start_ts, $end_ts, $date, $tag, $language);
        }

        return [
            'data' => $result,
            'total' => count($result)
        ];
    }

    private function autoChatTypeStatistics($start_ts, $end_ts, $date, $language)
    {
        $question_types = XssAutoQuestion::findQuestionTypes();

        $records = XsstAutoQuestionLog::findRecords($start_ts, $end_ts, $language);
        if (empty($records)) {
            return [];
        }

        $info = array();
        $index = 1;
        foreach ($records as $record) {
            $type = isset($question_types[$record['qid']]) ? $question_types[$record['qid']] : 0;
            if (!isset($info[$type])) {
                $info[$type] = [
                    'id' => $index,
                    'date' => $date,
                    'type' => $type,
                    'type_name' => isset(XssAutoQuestion::$QUESTION_TYPE[$type]) ? XssAutoQuestion::$QUESTION_TYPE[$type] : '-',
                    'hit_nums' => 0,
                    'useful_hit_nums' => 0,
                    'unuseful_hit_nums' => 0,
                    'convert_servers_nums' => 0,
                    'useful_hit_rate' => 0,
                    'convert_servers_rate' => 0,
                    'start_ts' => $start_ts,
                    'end_ts' => $end_ts,
                ];
                $index++;
            }
            $info[$type]['hit_nums'] += 1;
            $info[$type]['useful_hit_nums'] += $record['vote_type'] == 1 ? 1 : 0;
            $info[$type]['unuseful_hit_nums'] += $record['vote_type'] == 0 ? 1 : 0;
        }
        $this->formatRecordRate($info);

        return array_values($info);
    }

    private function autoChatQuestionStatistics($start_ts, $end_ts, $date, $tag, $language)
    {
        $questions = XssAutoQuestion::findQuestions($tag);
        if (empty($questions)) {
            return [];
        }
        $question_names = [];
        foreach ($questions as $question) {
            $question_names[$question['id']] = $question['subject'];
        }
        unset($questions);
        $qids = array_keys($question_names);

        $records = XsstAutoQuestionLog::findRecords($start_ts, $end_ts, $language, $qids);
        if (empty($records)) {
            return [];
        }

        $info = array();
        $index = 1;
        foreach ($records as $record) {
            if (!isset($info[$record['qid']])) {
                $info[$record['qid']] = [
                    'id' => $index,
                    'date' => $date,
                    'qid' => $record['qid'],
                    'question' => $question_names[$record['qid']] ?? '-',
                    'hit_nums' => 0,
                    'useful_hit_nums' => 0,
                    'unuseful_hit_nums' => 0,
                    'convert_servers_nums' => 0,
                    'useful_hit_rate' => 0,
                    'convert_servers_rate' => 0,
                    'start_ts' => $start_ts,
                    'end_ts' => $end_ts,
                ];
                $index++;
            }
            $info[$record['qid']]['hit_nums'] += 1;
            $info[$record['qid']]['useful_hit_nums'] += $record['vote_type'] == 1 ? 1 : 0;
            $info[$record['qid']]['unuseful_hit_nums'] += $record['vote_type'] == 0 ? 1 : 0;
        }
        $this->formatRecordRate($info);

        return array_values($info);
    }

    private function formatRecordRate(&$info)
    {
        foreach ($info as &$val) {
            $val['useful_hit_rate'] = $val['hit_nums'] != 0
                ? sprintf('%.2f%%', 100 * $val['useful_hit_nums'] / $val['hit_nums']) : 0;
        }
    }
}
