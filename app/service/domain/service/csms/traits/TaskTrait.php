<?php

namespace Imee\Service\Domain\Service\Csms\Traits;

use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsKanbanQuartile;
use Imee\Service\Domain\Service\Csms\Consts\CommonConst;
use Imee\Service\Domain\Service\Csms\Task\KanbanService;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

trait TaskTrait
{
    use UserInfoTrait;
    public static $userTree = [];
    public static $choiceList = [];

    /**
     * 渲染审核项看板
     * @param $text_arr
     * @param $v array 独项数据
     * @param $audit_item string 审核项
     */
    public static function _buildKanban(&$text_arr_detail, $v, $audit_item)
    {
        $app_id = isset($v['app_id']) && $v['app_id'] > 0 ? $v['app_id'] : 0;
        $review = isset($v['review']) ? ($v['review'] ? 1 : 0) : 0;
        $not_review = isset($v['review']) ? ($v['review'] ? 0 : 1) : 0;
		$area = $v['area'] ?? 0;
        $first_machine = 0;
        //初审
        if (isset($v['op']) && $v['op'] > 0) {
            // 新统计表统计
            // 判断是否机审
            $is_machine = $v['op'] == CommonConst::IMAGE_OP || $v['op'] == CommonConst::SYSTEM_OP ? 1 : 0;
            $first_machine = $is_machine;
            $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area] = $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area] ?? array(
                    'audited' => 0,
                    'audit_time' => 0,
                    'audit_time_array' =>[],
                    'review' => 0,
                    'not_review' => 0,
                    'audit_ten' => 0,
                    'pass_num' => 0,
                    'wrong_pass' => 0,
                    'refuse' => 0,
                    'wrong_refuse' => 0,
                    'refuse_time' => 0,
                    'refuse_ten' => 0
                );
            $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['audited'] += 1; //审核量
            $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['audit_time'] += $v['op_dateline'] - $v['dateline']; //审核总时长
            if (!$is_machine) {
                $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['audit_time_array'][] = $v['op_dateline'] - $v['dateline'];
            }
            $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['review'] += $review; // 先审后发量
            $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['not_review'] += $not_review; // 先发后审量
            if ($v['op_dateline'] - $v['dateline'] <= 10) {
                $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['audit_ten'] += 1; //10秒完审量
            }
            if ($v['deleted'] == 1) {
                $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['pass_num'] += 1; //通过量
                if ((isset($v['op3']) && $v['op3'] > 0 && $v['deleted3'] != 1) || isset($v['wrong_done'])) {
                    $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['wrong_pass'] += 1; //误通过数
                }
            } elseif ($v['deleted'] == 2) {
                $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['refuse'] += 1; //拒绝量
                if ((isset($v['op3']) && $v['op3'] > 0 && $v['deleted3'] != 2) || isset($v['wrong_done'])) {
                    $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['wrong_refuse'] += 1; //误拒绝数
                }
                $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['refuse_time'] += $v['op_dateline'] - $v['dateline']; //审核拒绝总时长
                if ($v['op_dateline'] - $v['dateline'] <= 10) {
                    $text_arr_detail['op'][$v['op']][$audit_item][$app_id][$is_machine][$area]['refuse_ten'] += 1; //10秒完审量
                }
            }
        }

        //复审
        if (isset($v['op2']) && $v['op2'] > 0) {
            // 新统计表统计
            $is_machine = $v['op2'] == CommonConst::IMAGE_OP || $v['op2'] == CommonConst::SYSTEM_OP ? 1 : 0;
            $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area] = $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area] ?? array(
                    'audited' => 0,
                    'audit_time' => 0,
                    'review' => 0,
                    'not_review' => 0,
                    'audit_ten' => 0,
                    'pass_num' => 0,
                    'wrong_pass' => 0,
                    'refuse' => 0,
                    'wrong_refuse' => 0,
                    'refuse_time' => 0,
                    'refuse_ten' => 0
                );
            $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['audited'] += 1; //审核量
            $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['audit_time'] += $v['op_dateline2'] - $v['op_dateline']; //审核总时长
            if ($first_machine) {
                $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['audit_time_array'][] = $v['op_dateline2'] - $v['op_dateline'];
            }
            $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['review'] += $review; // 先审后发量
            $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['not_review'] += $not_review; // 先发后审量
            if ($v['op_dateline2'] - $v['op_dateline'] <= 10) {
                $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['audit_ten'] += 1; //10秒完审量
            }
            if ($v['deleted2'] == 1) {
                $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['pass_num'] += 1; //通过量
                if ((isset($v['op3']) && $v['op3'] > 0 && $v['deleted3'] != 1) || isset($v['wrong_done'])) {
                    $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['wrong_pass'] += 1; //误通过数
                }
            } elseif ($v['deleted2'] == 2) {
                $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['refuse'] += 1; //拒绝量
                if ((isset($v['op3']) && $v['op3'] > 0 && $v['deleted3'] != 2) || isset($v['wrong_done'])) {
                    $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['wrong_refuse'] += 1; //误拒绝数
                }
                $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['refuse_time'] += $v['op_dateline2'] - $v['op_dateline']; //审核拒绝总时长
                if ($v['op_dateline2'] - $v['op_dateline'] <= 10) {
                    $text_arr_detail['op2'][$v['op2']][$audit_item][$app_id][$is_machine][$area]['refuse_ten'] += 1; //10秒完审量
                }
            }
        }

        //质检
        if (isset($v['op3']) && $v['op3'] > 0) {
            $is_machine = $v['op3'] == CommonConst::IMAGE_OP || $v['op3'] == CommonConst::SYSTEM_OP ? 1 : 0;
            if ($v['op2'] > 0) {
                $dateline = $v['op_dateline2'];
            } else {
                $dateline = $v['op_dateline'];
            }

            // 新统计表统计
            $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area] = $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area] ?? array(
                    'audited' => 0,
                    'audit_time' => 0,
                    'review' => 0,
                    'not_review' => 0,
                    'audit_ten' => 0,
                    'pass_num' => 0,
                    'wrong_pass' => 0,
                    'refuse' => 0,
                    'wrong_refuse' => 0,
                    'refuse_time' => 0,
                    'refuse_ten' => 0
                );
            $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['audited'] += 1; //审核量
            $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['audit_time'] += $v['op_dateline3'] - $dateline; //审核总时长
            $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['review'] += $review; // 先审后发量
            $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['not_review'] += $not_review; // 先发后审量
            if ($v['op_dateline3'] - $dateline <= 10) {
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['audit_ten'] += 1; //10秒完审量
            }
            if ($v['deleted3'] == 1) {
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['pass_num'] += 1; //通过量
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['wrong_pass'] = 0; //误通过数
            } elseif ($v['deleted3'] == 2) {
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['refuse'] += 1; //拒绝量
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['wrong_refuse'] = 0; //误拒绝数
                $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['refuse_time'] += $v['op_dateline3'] - $dateline; //审核拒绝总时长
                if ($v['op_dateline3'] - $dateline <= 10) {
                    $text_arr_detail['op3'][$v['op3']][$audit_item][$app_id][$is_machine][$area]['refuse_ten'] += 1; //10秒完审量
                }
            }
        }
    }

    /**
     * 渲染审核项看板
     * @param $text_arr
     * @param $v array 独项数据
     * @param $audit_item string 审核项
     */
    public static function _buildKanbanNew(&$time_array, &$text_arr_detail, $v, $audit_item, $start_time, &$countList)
    {
        $review = isset($v['review']) ? ($v['review'] ? 1 : 0) : 0;
        $not_review = isset($v['review']) ? ($v['review'] ? 0 : 1) : 0;
        $type = self::getType($v);
        $data_list = [];
        // 维度
        if (isset($v['op']) && $v['op'] > 0 && $v['op_dateline'] >= $start_time && $v['op_dateline'] < ($start_time + 86400)) {
            // 当日初审数据统计
            $is_machine = $v['op'] > 9000 ? 1 : 0;
            $first_time = $v['dateline']; // 处理时长计算的初始时间
            $data_list[] = array(
                'verify_type' => $is_machine ? 'op_machine' : 'op', // 初审不外显和初审外显
                'is_machine' => $is_machine,
                'admin' => $v['op'],
                'spend_time' => max($v['op_dateline'] - $first_time, 0),
                'first_result' => $v['deleted'],
                'final_result' => (isset($v['deleted3']) && isset($v['op3']) && $v['op3'] > 0) ? $v['deleted3'] : ((isset($v['deleted2']) && isset($v['op2']) && $v['op2'] > 0) ? $v['deleted2'] : $v['deleted']),
                'area' => $v['area'],
                'audit_time' => $v['op_dateline'],
            );
        }
        if (isset($v['op2']) && $v['op2'] > 0 && $v['op_dateline2'] >= $start_time && $v['op_dateline2'] < ($start_time + 86400)) {
            // 复审结果处理
            $is_machine = $v['op2'] > 9000 ? 1 : 0;
            $review = isset($v['op']) && $v['op'] > 9000 ? 0 : 1;
            $first_time = $v['op_dateline'] ?? $v['dateline']; // 处理时长计算的初始时间
            $data_list[] = array(
                'verify_type' => $review ? 'op2_machine' : 'op2', // 复审不外显和复审外显
                'is_machine' => $is_machine,
                'admin' => $v['op2'],
                'spend_time' => max($v['op_dateline2'] - $first_time, 0),
                'first_result' => $v['deleted2'],
                'final_result' => (isset($v['deleted3']) && isset($v['op3']) && $v['op3'] > 0) ? $v['deleted3'] : $v['deleted2'],
                'area' => $v['area'],
                'audit_time' => $v['op_dateline2'],
            );
        }
        if (isset($v['op3']) && $v['op3'] > 0 && $v['op_dateline3'] >= $start_time && $v['op_dateline3'] < ($start_time + 86400)) {
            // 复审结果处理
            $is_machine = $v['op3'] > 9000 ? 1 : 0;
            $review = 0;
            $first_time = $v['op_dateline2'] ?? ($v['op_dateline'] ?? $v['dateline']); // 处理时长计算的初始时间
            $data_list[] = array(
                'verify_type' => $review ? 'op3' : 'op3_machine', // 复审不外显
                'is_machine' => $is_machine,
                'admin' => $v['op3'],
                'spend_time' => max($v['op_dateline3'] - $first_time, 0),
                'first_result' => $v['deleted3'],
                'final_result' => $v['deleted3'],
                'area' => $v['area'],
                'audit_time' => $v['op_dateline3'],
            );
        }
        if ($v['dateline'] >= $start_time && $v['dateline'] < ($start_time + 86400)) {
            // 当日进线
            $countList[$audit_item][$v['area']][$type][APP_ID] = $countList[$audit_item][$v['area']][$type][APP_ID] ?? [
                    'total_num' => 0
                ];
            $countList[$audit_item][$v['area']][$type][APP_ID]['total_num']++;
        }
        if ($data_list) {
            foreach ($data_list as $item) {
                self::buildAuditData(array(
                    'audit_item' => $audit_item,
                    'verify_type' => $item['verify_type'],
                    'is_machine' => $item['is_machine'],
                    'area' => $item['area'],
                    'admin' => $item['admin'],
                    'review' => $review,
                    'not_review' => $not_review,
                    'spend_time' => $item['spend_time'],
                    'first_result' => $item['first_result'],
                    'final_result' => $item['final_result'],
                    'type' => $type,
                ), $text_arr_detail);
//                $time_array[] = array(
//                    'audit_item' => $audit_item,
//                    'dateline' => $item['audit_time'],
//                    'verify_type' => $item['verify_type'],
//                    'operator' => $item['admin'],
//                    'type' => $type,
//                    'spend_time' => $item['spend_time'],
//                    'area' => $item['area']
//                );
            }
        }
    }

    /**
     * @param array $data
     * @return void
     */
    public static function buildAuditData(array $data, &$text_arr_detail = [])
    {
        $audit_item = $data['audit_item'];
        $verify_type = $data['verify_type'];
        $is_machine = $data['is_machine'];
        $area = $data['area'];
        $admin = $data['admin'];
        $review = $data['review'];
        $not_review = $data['not_review'];
        $spend_time = $data['spend_time'];
        $first_result = $data['first_result'];
        $final_result = $data['final_result'];
        $type = $data['type'];

        // 新统计表统计
        $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area] = $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area] ?? array(
                'audited' => 0,
                'audit_time' => 0,
                'review' => 0,
                'not_review' => 0,
                'audit_ten' => 0,
                'pass_num' => 0,
                'wrong_pass' => 0,
                'refuse' => 0,
                'wrong_refuse' => 0,
                'refuse_time' => 0,
                'refuse_ten' => 0,
                'type' => $type,
            );
        $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['audited'] += 1; //审核量
        $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['audit_time'] += $spend_time; //审核总时长
        $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['review'] += $review; // 先审后发量
        $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['not_review'] += $not_review; // 先发后审量
        if ($spend_time <= 10) {
            $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['audit_ten'] += 1; //10秒完审量
        }
        if ($first_result == 1) {
            $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['pass_num'] += 1; //通过量
            if ($final_result != $first_result) {
                $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['wrong_pass'] += 1; //误通过数
            }
        } elseif ($first_result == 2) {
            $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['refuse'] += 1; //通过量
            if ($final_result != $first_result) {
                $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['wrong_refuse'] += 1; //误通过数
            }
            $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['refuse_time'] += $spend_time; //审核拒绝总时长
            if ($spend_time <= 10) {
                $text_arr_detail[$verify_type][$admin][$audit_item][$is_machine][$area]['refuse_ten'] += 1; //10秒完审量
            }
        }
    }

    /**
     * 获取审核项类型
     * @param array $value
     * @return string
     */
    private static function getType(array $value)
    {
        if (isset($value['value']) && $value['value']) {
            $values = @json_decode($value['value'], true);
            if ($values && is_array($values)) {
                $allType = array_column($values, 'type');
                if (in_array('video', $allType)) {
                    return 'video';
                }
                if (in_array('audio', $allType)) {
                    return 'audio';
                }
                if (in_array('image', $allType)) {
                    return 'image';
                }
                if (in_array('text', $allType)) {
                    return 'text';
                }
            }
        }
        return 'text';
    }

    /**
     * @param array $text_arr
     * @return int|void
     */
    public static function _insertKanban(array $textArr)
    {
        //文本审核入库
        if (empty($textArr)) {
            return 0;
        }
        $strArr = array();
        foreach ($textArr as $text) {
            if (empty($text)) {
                continue;
            }
            $strArr[] = array(
                'audit_item' => $text['audit_item'],
                'dateline' => $text['dateline'],
                'verify_type' => $text['verify_type'],
                'admin' => $text['operator'],
                'type' => $text['type'],
                'spend_time' => $text['spend_time'],
                'area' => $text['area'],
            );
        }
        unset($textArr);
        $newStrArr = array_chunk($strArr, 1000);
        unset($strArr);
        foreach ($newStrArr as $item) {
            CsmsKanbanQuartile::addBatch($item);
            usleep(10000);
        }
        unset($newStrArr);
    }

    /**
     * 渲染审核项分时看板
     * @param $text_arr
     * @param $v array 独项数据
     * @param $audit_item string 审核项
     */
    public static function _buildKanbanTime(&$text_arr_detail, $v, $audit_item)
    {
        $hour = date('H', $v['op_dateline']);
        switch ($hour) {
            case 0:
            case 1:
            case 2:
            case 3:
                $hour_name = '0-3';
                break;
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                $hour_name = '4-9';
                break;
            case 10:
            case 11:
            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 17:
            case 18:
                $hour_name = '10-19';
                break;
            case 19:
            case 20:
            case 21:
            case 22:
            case 23:
                $hour_name = '19-24';
                break;
        }
        //初审
        if ($v['op'] > 0) {
            // 新统计表统计
            // 判断是否机审
            $is_machine = $v['op'] == CommonConst::IMAGE_OP || $v['op'] == CommonConst::SYSTEM_OP ? 1 : 0;
            $text_arr_detail[$audit_item][$is_machine][$hour_name]['audited'] += 1; //审核量
            $text_arr_detail[$audit_item][$is_machine][$hour_name]['audit_time'] += $v['op_dateline'] - $v['dateline']; //审核总时长
        }
    }

    /**
     * 根据用户id数组，获取用户id对应appid数组
     * @param array $user_ids
     * @return array
     */
    public static function getAppByUser(array $user_ids)
    {
        $r = [];
        $need = [];
        foreach ($user_ids as $item) {
            if (isset(self::$userTree[$item])) {
                $r[$item] = self::$userTree[$item];
            } else {
                $need[] = $item;
            }
        }
        $res = [];
        if ($need) {
            $res = self::userInfo($need);
            $res = array_column($res, 'app_id', 'uid');
        }
        return $r + $res;
    }

    /**
     * p90
     * @param $data
     * @return int|mixed|string|null
     */
    public static function calP90($data = array())
    {
        if (empty($data)) {
            return 0;
        }
        $n = count($data);
        if ($n == 1) {
            return array_pop($data);
        }
        sort($data);
        $b = ($n - 1) * 0.9;
        $i = intval($b);
        $j = $b - $i;
        return sprintf('%.2f', (1 - $j) * $data[$i] + $j * $data[$i + 1]);
    }

    /**
     * 计算p90平均值 ： 比p90值小，再计算平均
     * @param $p90
     * @param array $data
     * @return  float
     */
    public static function calP90Ave($p90, $data = array())
    {
        foreach ($data as $k => $v) {
            if ($v > $p90) {
                unset($data[$k]);
            }
        }
        $count = count($data);
        if (empty($count)) {
            return 0;
        }

        return sprintf('%.2f', array_sum($data)/$count);
    }

    /**
     * 插入p90分位数
     * @param array $audit_time_array
     * @param $start_time
     * @return false|int
     */
    public static function saveQuartile(array $audit_time_array, $start_time)
    {
        if ($audit_time_array) {
            foreach ($audit_time_array as $key => $item) {
                $p90 = self::calP90($item);
                $p90_average = self::calP90Ave($p90, $item);
                $arrInsert[] = "('".$key."',{$start_time},{$p90},{$p90_average})";
            }
            $strSqlInsert = implode(',', $arrInsert);
            $insert = "insert into csms_verify_kanban_quartile (`audit_item`,`dateline`,`p90`,`p90_average`) values {$strSqlInsert}";
            return Helper::exec($insert, 'xssdb');
        } else {
            echo "没有符合要求的数据".PHP_EOL;
        }
        return false;
    }

    /**
     * @param array $audit_time_array
     * @return array|array[]
     */
    public static function makeQuartile(array $audit_time_array)
    {
        if ($audit_time_array) {
            $new = [
                'total' => []
            ];
            foreach ($audit_time_array as $item) {
                $new['total'] = array_merge($new['total'], $item);
            }
            return $new;
        }
        return [];
    }

    /**
     * @param $text_arr_detail
     * @param $v
     * @param $audit_item
     * @param $start_time
     * @return false|void
     */
    public static function _buildQuartile(&$text_arr_detail, $v, $audit_item, $start_time)
    {
        if (!isset($v['op'])) {
            return false;
        }
        // 初审是否机审
        $first_machine = self::isMachine($v['op']);
        $p90 = KanbanService::getP90($start_time);
        if ($first_machine) {
            // 初审是机审
            if (isset($v['op2']) && $v['op2'] > 0) {
                if (isset($p90[$start_time][$audit_item]) && $p90[$start_time][$audit_item] < ($v['op_dateline2'] - $v['dateline'])) {
                    $text_arr_detail[$v['op2']][$audit_item]['p90_up'] = $text_arr_detail[$v['op2']][$audit_item]['p90_up'] ?? 0;
                    $text_arr_detail[$v['op2']][$audit_item]['p90_up']++;
                }
                if (isset($p90[$start_time]['total']) && $p90[$start_time]['total'] < ($v['op_dateline2'] - $v['dateline'])) {
                    $text_arr_detail[$v['op2']][$audit_item]['p90_day_up'] = $text_arr_detail[$v['op2']][$audit_item]['p90_day_up'] ?? 0;
                    $text_arr_detail[$v['op2']][$audit_item]['p90_day_up']++;
                }
            }
        } else {
            if (isset($v['op']) && $v['op'] > 0) {
                if (isset($p90[$start_time][$audit_item]) && $p90[$start_time][$audit_item] < ($v['op_dateline'] - $v['dateline'])) {
                    $text_arr_detail[$v['op']][$audit_item]['p90_up'] = $text_arr_detail[$v['op']][$audit_item]['p90_up'] ?? 0;
                    $text_arr_detail[$v['op']][$audit_item]['p90_up']++;
                }
                if (isset($p90[$start_time]['total']) && $p90[$start_time]['total'] < ($v['op_dateline'] - $v['dateline'])) {
                    $text_arr_detail[$v['op']][$audit_item]['p90_day_up'] = $text_arr_detail[$v['op']][$audit_item]['p90_day_up'] ?? 0;
                    $text_arr_detail[$v['op']][$audit_item]['p90_day_up']++;
                }
            }
        }
    }

    /**
     * 文本图片审核结果
     * @param $text_arr_detail
     * @param $v
     * @param $audit_item
     * @return void
     */
    public static function _buildMachineData(&$text_arr_detail, $v, $audit_item)
    {
        $type = $v['type']; // 1文本 2图片
        $is_machine = isset($v['op']) ? self::isMachine($v['op']) : 0;
        $machine_result = self::getResult($v);
        $have_result = $v['have_result'];
        $pass = $v['pass'];
        $refuse = $v['refuse'];
        // 统计
        if (!isset($text_arr_detail[$audit_item][$type])) {
            $text_arr_detail[$audit_item][$type] = array(
                'total' => 0,
                'machine_valid_total' => 0,
                'machine_exact_total' => 0,
                'machine_total' => 0,
                'machine_pass_num' => 0,
                'machine_refuse_num' => 0,
                'pass_exact_num' => 0,
                'refuse_exact_num' => 0,
            );
        }
        $text_arr_detail[$audit_item][$type]['total']++;
        $text_arr_detail[$audit_item][$type]['machine_valid_total'] += $is_machine;
        $text_arr_detail[$audit_item][$type]['machine_exact_total'] += $machine_result;
        $text_arr_detail[$audit_item][$type]['machine_total'] += $have_result;
        $text_arr_detail[$audit_item][$type]['machine_pass_num'] += $pass;
        $text_arr_detail[$audit_item][$type]['machine_refuse_num'] += $refuse;
        if ($machine_result && $pass) {
            $text_arr_detail[$audit_item][$type]['pass_exact_num'] ++;
        }
        if ($machine_result && $refuse) {
            $text_arr_detail[$audit_item][$type]['refuse_exact_num'] ++;
        }
    }

    /**
     * @param array $data
     * @return int
     */
    private static function getResult(array $data)
    {
        if ( isset($data['op3']) && $data['op3'] > 0 ) {
            if ($data['deleted3'] == $data['machine_deleted']) {
                return 1;
            }
        } elseif ( isset($data['op2']) && $data['op2'] > 0 ) {
            if ($data['deleted2'] == $data['machine_deleted']) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @param array $condition
     * @return array
     */
    private static function filter(array $condition)
    {
        return array_filter($condition, function ($item) {
            if ($item === '' || $item === null || $item === ['']) {
                return false;
            }
            return true;
        });
    }

    // 打印内存使用情况
    private static function debugM($type = '', $str = '')
    {
        echo "[" . date("Y-m-d H:i:s") . "][$type][$str]::" . self::debugMemConvert(memory_get_usage(true)) . PHP_EOL;
    }

    // 内存占用大小输出转换
    private static function debugMemConvert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . strtoupper($unit[$i]);
    }

    // 是否机审
    private static function isMachine($op)
    {
        return (isset($op) && ($op == CommonConst::IMAGE_OP || $op == CommonConst::SYSTEM_OP)) ? 1 : 0;
    }
}