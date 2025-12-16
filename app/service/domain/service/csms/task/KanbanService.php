<?php

namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsKanbanAuditCount;
use Imee\Models\Xss\CsmsKanbanQuartile;
use Imee\Models\Xss\CsmsKanbanQuartileMonth;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Models\Xss\CsmsVerifyKanbanDetail;
use Imee\Models\Xss\CsmsVerifyKanbanQuartile;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Consts\CommonConst;
use Imee\Service\Domain\Service\Csms\Process\Databoard\ExamProcess;
use Imee\Service\Domain\Service\Csms\Traits\TaskTrait;
use Imee\Service\Helper;

class KanbanService
{
    use TaskTrait;
    public static $p90 = [];

    public static $items = [

    ];

    public static $choicesList = [];

    /**
     * @param array $text_arr
     * @param $start_time
     * @return int
     */
    public static function _insertKanbanDetail(array $text_arr, $start_time)
    {
        //文本审核入库
        if (empty($text_arr) || (empty($text_arr['op']) && empty($text_arr['op2'] && empty($text_arr['op3'])))) {
            return 0;
        }
        $strArr = array();
        foreach ($text_arr as $verify_type => $text) {
            if (!empty($text)) {
                foreach ($text as $admin => $admin_data) {
                    foreach ($admin_data as $k => $audit_item_new) {
                            foreach ($audit_item_new as $is_machine => $area_item) {
								foreach ($area_item as $area => $audit_item) {
									$audit_item = self::getAuditItem($audit_item); //审核量
									if (empty($audit_item['review'])) $audit_item['review'] = 0; //先审后发量
									if (empty($audit_item['not_review'])) $audit_item['not_review'] = 0; //先发后审量
									$scoreNinety = 0;//isset($audit_item['audit_time_array']) ? self::calP90($audit_item['audit_time_array']) : 0;
                                    $strArr[] = array(
                                        'admin' => $admin,
                                        'verify_type' => $verify_type,
                                        'audit_item' => $k,
                                        'dateline' => $start_time,
                                        'pending_trial' => 0,
                                        'audited' => $audit_item['audited'],
                                        'pass_num' => $audit_item['pass_num'],
                                        'refuse' => $audit_item['refuse'],
                                        'wrong_pass' => $audit_item['wrong_pass'],
                                        'wrong_refuse' => $audit_item['wrong_refuse'],
                                        'audit_time' => $audit_item['audit_time'],
                                        'refuse_time' => $audit_item['refuse_time'],
                                        'audit_ten' => $audit_item['audit_ten'],
                                        'refuse_ten' => $audit_item['refuse_ten'],
                                        'review' => $audit_item['review'],
                                        'is_machine' => $is_machine,
                                        'not_review' => $audit_item['not_review'],
                                        'score_nine' => $scoreNinety,
                                        'area' => $area,
                                        'type' => $audit_item['type'],
                                    );
                                }
                            }
                    }
                }
            }
        }

        $chunkStrArr = array_chunk($strArr, 1000);
        foreach ($chunkStrArr as $item) {
            CsmsVerifyKanbanDetail::addBatch($item);
        }
        unset($chunkStrArr);
    }


    /**
     * @param array $text_arr
     * @param $start_time
     * @return int
     */
    public static function _insertQuartile(array $text_arr, $start_time)
    {
        if (empty($text_arr)) {
            return 0;
        }
        $strArr = array();
        foreach ($text_arr as $admin => $text) {
            foreach ($text as $audit_item => $admin_data) {
                $p90_up = $admin_data['p90_up'] ?? 0;
                $p90_day_up = $admin_data['p90_day_up'] ?? 0;
                $strArr[] = "({$admin}, '{$audit_item}','{$start_time}','{$p90_up}','{$p90_day_up}')";
            }
        }
        $strSql = implode(',', $strArr);
        $sql = "insert into csms_verify_kanban_quartile_admin (`admin`,`audit_item`,`dateline`,`p90_up`,`p90_day_up`) values " . $strSql;
        return Helper::exec($sql, 'bmsdb');
    }

    /**
     * @param $audit_item
     * @return mixed
     */
    private static function getAuditItem($audit_item)
    {
        if (empty($audit_item['audited'])) {
            $audit_item['audited'] = 0;
        } //审核量
        if (empty($audit_item['audit_time']) || $audit_item['audit_time'] < 0) {
            $audit_item['audit_time'] = 0;
        } //审核总时长
        if (empty($audit_item['audit_ten'])) {
            $audit_item['audit_ten'] = 0;
        } //10秒完审量
        if (empty($audit_item['pass_num'])) {
            $audit_item['pass_num'] = 0;
        }  //通过量
        if (empty($audit_item['wrong_pass'])) {
            $audit_item['wrong_pass'] = 0;
        } //误通过数
        if (empty($audit_item['wrong_refuse'])) {
            $audit_item['wrong_refuse'] = 0;
        } //误拒绝量
        if (empty($audit_item['refuse'])) {
            $audit_item['refuse'] = 0;
        } //拒绝量
        if (empty($audit_item['refuse_time'])) {
            $audit_item['refuse_time'] = 0;
        } //审核拒绝总时长
        if (empty($audit_item['refuse_ten'])) {
            $audit_item['refuse_ten'] = 0;
        }
        return $audit_item;
    }

    /**
     * @param array $item
     * @return mixed|string
     */
    public static function auditDeal(array $item)
    {
        //审核项
        if ($item['table'] == 'xs_user_profile') {
            if ($item['field'] == 'tmp_icon') {
                $audit_item = 'tmp_icon';
            } elseif ($item['field'] == 'god_tmp_icon') {
                $audit_item = 'god_tmp_icon';
            } else {
                $audit_item = 'nickname';
            }
        } elseif($item['table'] == 'xs_fleet'){
            if($item['field'] == 'tmp_icon'){
                $audit_item = 'xs_fleet_icon';
            }else{
                $audit_item = $item['table'];
            }
        } else {
            $audit_item = $item['table'];
        }
        return $audit_item;
    }

    /**
     * @param $start_time
     * @return array|mixed
     */
    public static function getP90($start_time)
    {
        if (!isset(self::$p90[$start_time])) {
            $quartile = CsmsVerifyKanbanQuartile::handleList(self::filter(array(
                'columns' => ['audit_item', 'p90'],
                'day' =>  $start_time,
            )));
            self::$p90[$start_time] = array_column($quartile, 'p90', 'audit_item');
        }
        return self::$p90;
    }

    /**
     * @param $type
     * @param array $audits
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function auditTotal($type, array $audits, $start_time, $end_time)
    {
        $list = CsmsVerifyKanbanDetail::handleList(array(
            'audit_item_array' => $audits,
            'verify_type' => 'op',
            'dateline_start' => $start_time,
            'dateline_end' => $end_time,
            'groupBy' => 'audit_item',
            'columns' => ['audit_item', 'sum(audited) as audited']
        ));
        if ($list) {
            $text_arr = [];
            foreach ($list as $item) {
                if (!$item['audit_item']) continue;
                $text_arr[$item['audit_item']][$type] = array(
                    'total' => $item['audited'],
                    'machine_valid_total' => 0,
                    'machine_exact_total' => 0,
                    'machine_total' => 0,
                    'machine_pass_num' => 0,
                    'machine_refuse_num' => 0,
                    'pass_exact_num' => 0,
                    'refuse_exact_num' => 0,
                );
                KanbanService::_insertMachine($text_arr, $start_time);
            }
        } else {
            echo "未找到审核项详情\n";
        }
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function auditInsert($start_time, $end_time)
    {
        foreach (CommonConst::audit_item as $type => $audit) {
            self::auditTotal($type, array_values($audit), $start_time, $end_time);
        }
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function deleteWithDate($start_time, $end_time)
    {
        CsmsVerifyKanbanDetail::deleteByWhere(array(
            ['dateline', '>=', $start_time],
            ['dateline', '<', $end_time],
            ['source', '=', 1],
        ));
//        CsmsKanbanQuartile::deleteByWhere(array(
//            ['dateline', '>=', $start_time],
//            ['dateline', '<', $end_time],
//            ['source', '=', 1],
//        ));
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function deleteWithDateQuartileAdmin($start_time, $end_time)
    {
        $sql = "DELETE from csms_verify_kanban_quartile_admin where dateline >= {$start_time} and dateline < {$end_time}";
        Helper::exec($sql, 'bmsdb');
    }

    /**
     * @param array $text_arr
     * @param $start_time
     * @return int
     */
    public static function _insertMachine(array $text_arr, $start_time)
    {
        if (empty($text_arr)) {
            return 0;
        }
        $strArr = array();
        foreach ($text_arr as $audit_item => $text) {
            foreach ($text as $type => $admin_data) {
                $week = strtotime('next Monday', $start_time) - 60*60*24*7; // 周一0点
                $strArr[] = "({$start_time}, '{$week}','{$audit_item}','{$type}','{$admin_data['total']}','{$admin_data['machine_total']}','{$admin_data['machine_valid_total']}','{$admin_data['machine_exact_total']}','{$admin_data['machine_pass_num']}','{$admin_data['machine_refuse_num']}','{$admin_data['refuse_exact_num']}','{$admin_data['pass_exact_num']}')";
            }
        }
        $strSql = implode(',', $strArr);
        $sql = "insert into csms_kanban_machine (`dateline`,`week`,`audit`,`type`,`total`,`machine_total`,`machine_valid_total`,`machine_exact_total`,`machine_pass_num`,`machine_refuse_num`,`refuse_exact_num`,`pass_exact_num`) values " . $strSql . "on duplicate key update `total` = {$admin_data['total']}";
        return Helper::exec($sql, 'xssdb');
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function deleteMachine($start_time, $end_time)
    {
        $sql = "DELETE from csms_kanban_machine where dateline >= {$start_time} and dateline < {$end_time}";
        Helper::exec($sql, 'xssdb');
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return void
     */
    public static function summitData($start_time, $end_time)
    {
        $limit = 5000;
        $offset = 0;
        $textArrDetail = array(
            'op' => array(),
            'op2' => array(),
            'op3' => array(),
        );
        $textArr = [];
        $countList = [];
        while (true) {
            $condition = array(
                'dateline_start' => $end_time - 7*86400,
                'dateline_end' => $end_time,
                'columns' => 'id, op,op_dateline,deleted,deleted2,op2,op_dateline2,deleted3,op3,op_dateline3,dateline,review,choice,app_id,area,value',
                'orderBy' => 'id',
                'limit' => $limit,
                'id_lg' => $offset
            );
            $arr = CsmsAudit::handleList($condition);
            if (empty($arr)) {
                if ($offset === 0) {
                    echo "csms_audit 没有符合要求的数据".PHP_EOL;
                }
                break;
            }
            foreach ($arr as $item) {
                // 渲染
                KanbanService::_buildKanbanNew($textArr, $textArrDetail, $item, $item['choice'], $start_time, $countList);
                unset($item);
            }
            $last = array_pop($arr);
            $offset = $last['id'];
            usleep(10000);
            unset($arr);
            if (count($textArr) >= 5000) {
                //文本审核入库
//                KanbanService::_insertKanban($textArr);
                $textArr = [];
            }
            if (count($countList) >= 5000) {
                KanbanService::_insertKanbanCount($countList, $start_time);
                $countList = [];
            }
        }
        if ($textArr) {
            //文本审核入库
//            KanbanService::_insertKanban($textArr);
            $textArr = [];
        }
        //新审核统计入库
        KanbanService::_insertKanbanDetail($textArrDetail, $start_time);
        if ($countList) {
            KanbanService::_insertKanbanCount($countList, $start_time);
            $countList = [];
        }
        $textArrDetail = [];
        self::$choiceList = [];
    }

    public static function getChoices()
    {
        if (empty(self::$choicesList)) {
            self::$choicesList = CsmsChoice::getListByWhere(array(
                ['state', '=', 1]
            ), 'choice, extra');
        }
        $choice = self::$choicesList;
        $AllChoice = [];
        if ($choice) {
            foreach ($choice as $item) {
                $AllChoice['all'] = $AllChoice['all'] ?? [];
                $AllChoice['all'][] = $item['choice'];
                $extra = json_decode($item['extra'], true);
                if (isset($extra['audit_type']) && $extra['audit_type'] == 'kefu') {
                    $AllChoice['kefu_choice'] = $AllChoice['kefu_choice'] ?? [];
                    $AllChoice['kefu_choice'][] = $item['choice'];
                } else {
                    $AllChoice['audit_choice'] = $AllChoice['audit_choice'] ?? [];
                    $AllChoice['audit_choice'][] = $item['choice'];
                }
            }
        }
        return $AllChoice;
    }

    /**
     * @param array $text_arr
     * @param $start_time
     * @return int
     */
    public static function _insertKanbanCount(array $textArr, $start_time)
    {
        $strArr = array();
        foreach ($textArr as $auditItem => $v) {
            foreach ($v as $area => $m) {
                foreach ($m as $type => $n) {
                    foreach ($n as $appId => $item) {
                        $strArr[$appId] = $strArr[$appId] ?? [];
                        $strArr[$appId][] = array(
                            'dateline' => $start_time,
                            'audit_item' => $auditItem,
                            'area' => $area,
                            'type' => $type,
                            'total_num' => $item['total_num'],
                            'source' => 1,
                        );
                        self::addCount($strArr, 1000);
                    }
                }
            }
        }
        if ($strArr) {
            self::addCount($strArr);
        }
        unset($strArr);
    }

    /**
     * @param array $strArr
     * @return void
     */
    public static function addCount(array &$strArr, $limit = 0)
    {
        foreach ($strArr as $app => $vv) {
            if (count($vv) >= $limit) {
                $chunkStrArr = array_chunk($vv, 1000);
                foreach ($chunkStrArr as $item) {
                    switch ($app) {
                        case APP_ID:
                            // pt
                            CsmsKanbanAuditCount::addBatch($item);
                            break;
                        default:
                            break;
                    }
                }
                unset($chunkStrArr);
                $strArr[$app] = [];
            }
        }
    }

    /**
     * @param $month
     * @return void
     */
    public static function deleteQuartile($month)
    {
        CsmsKanbanQuartileMonth::deleteByWhere(array(
            ['month', '=', $month],
        ));
    }

    /**
     * 月纬度p90汇总
     * @param $month
     * @return void
     */
    public static function monthQuartile($month)
    {
        self::debugM('monthQuartile', '开始');
        $endMonth = strtotime('next month', $month);
        // 所有用户
        $users = CsmsUserChoice::getListByWhere(array(
            ['state', '=', 1]
        ), 'distinct user_id as user_id');
        $allUserIds = array_column($users, 'user_id');
        array_unshift($allUserIds, $allUserIds);
        // 所有审核项
        $AllChoice = self::getChoices();
        // 所有类型
        $allType = ['all', 'text', 'image', 'audio', 'video'];
        if ($allUserIds) {
            $process = new ExamProcess();
            $i = 0;
            foreach ($allUserIds as $userId) {
                foreach ($AllChoice as $nowChoice => $item) {
                    foreach ($allType as $type) {
                        $i++;
                        $p90Info = $process->getQuartileAdmin(array(
                            'admin' => $userId,
                            'type' => $type == 'all' ? '' : $type,
                            'audit_item' => $item,
                            'start' => $month,
                            'end' => $endMonth,
                            'verify_type' => 'op',
                            'force' => true,
                        ));
                        $insert = array(
                            'month' => $month,
                            'audit_item' => $nowChoice,
                            'admin' => is_array($userId) ? 0 : $userId,
                            'type' => $type,
                            'p90' => $p90Info['p90'],
                            'p90_ave' => $p90Info['ave_p90'],
                        );
                        CsmsKanbanQuartileMonth::add($insert);
                        self::debugM('monthQuartile', "完成{$i}次");
                    }
                }
            }
        }
        self::debugM('monthQuartile', '结束');
    }
}