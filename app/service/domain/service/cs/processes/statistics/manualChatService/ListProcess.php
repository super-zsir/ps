<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\ManualChatService;

use Imee\Models\Xs\XsBigarea;
use Imee\Service\Domain\Context\Cs\Statistics\ManualChatService\ListContext;
use Imee\Models\Xss\XssAutoService;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Phalcon\Di;
use Imee\Service\Helper;
use Imee\Service\Cachemanager\Cs\StatisticsManualChatServiceCache;

/**
 * 客服系统统计服务
 * @doc https://shimo.im/docs/x6xXqtXRk3J3DqGV/read
 * @desc 此接口发现存在较严重的性能问题
 */
class ListProcess
{
    use UserInfoTrait;

    protected $context;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
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

        if (!empty($this->context->serviceUid)) {
            $where['condition'][] = 'service_uid = :service_uid:';
            $where['bind']['service_uid'] = $this->context->serviceUid;
        }


        if (!empty($this->context->startTime)) {
            $where['condition'][] = 'service_start >= :service_start_time:';
            $where['bind']['service_start_time'] = strtotime($this->context->startTime);
        }

        if (!empty($this->context->endTime)) {
            $where['condition'][] = 'service_start < :service_end_time:';
            $where['bind']['service_end_time'] = strtotime($this->context->endTime) + 86400;
        }

        if (!empty($this->context->language)) {
            $where['condition'][] = 'language = :language:';
            $where['bind']['language'] = $this->context->language;
        }



        //只统计会话结束了的
        $where['condition'][] = 'end_time > :end_time:';
        $where['bind']['end_time'] = 0;

        $where['condition'][] = 'service in({services:array})';
        $where['bind']['services'] = array_keys(XssAutoService::$manualChatServiceConfig);

        return $where;
    }

    public function handle()
    {
        $where = $this->buildWhere();

        $autoServiceList = XssAutoService::find([
            'conditions' => implode(' and ', $where['condition']),
            'bind' => $where['bind'],
            'order' => 'id desc',
        ])->toArray();
        return $this->formatList($autoServiceList);
    }

    protected function formatList($autoServiceList)
    {
        $format = [
            'total' => 0,
            'data' => [],
        ];
        if (empty($autoServiceList)) {
            return $format;
        }

        $languageStr = '所有';
        if (!empty($this->context->language)) {
            $languageStr = XsBigarea::getBigAreaCnName($this->context->language);
        }

        $service = [];
        $serviceUids = [];
        foreach ($autoServiceList as $v) {
            $serviceUids[] = $v['service_uid'];
            $serviceID  = $v['service'].$v['app_id'].$v['service_uid'];//后台客服通道ID
            $service[$serviceID][] = $v;
        }
        unset($autoServiceList);

        $staffUserinfos = $this->getStaffBaseInfos(array_unique($serviceUids));

        $staticField = [
            'ask_sum' => 0,//人工咨询量
            'auto_over_sum' => 0,//自动完结量
            'system_service_timeout_sum' => 0,//客服超时
            'system_user_timeout_sum' => 0,//用户超时
            'man_over_sum' => 0,//手动完结量
            'ok_sum' => 0,//已解决
            'user_no_reply_sum' => 0,//对方无应答
            'no_answer_sum' => 0,//暂无解答
            'ave_man_over_sum' => 0,//平均完结时长
            'ave_first_response_time' => 0,//平均首次响应时长
            //'ave_response_time' => 0,//平均响应时长
            'ave_solve_time' => 0,//平均问题解决时长
            'p90_solve_time' => 0,//P90问题解决时长
            'p90_over_time' => 0,//P90问题完结时长
            'p90_response_time' => 0,//P90首次响应时长
            'p90_ave_solve_time' => 0,//P90问题平均解决时长
            'p90_ave_over_time' => 0,//P90问题平均完结时长
            'p90_ave_response_time' => 0,//P90首次平均响应时长
        ];

        

        $result = $p90_time = array();
        $row_id = count(array_keys(XssAutoService::$manualChatServiceConfig)) + 1;
        foreach ($service as $key => $item) {
            $service_time = $first_response_time = $ave_response_time = $ave_solve_time = array();
            $result[$key] = $staticField;
            $result[$key]['row_id'] = $row_id + 1;
            $result[$key]['ask_sum'] = count($item);
            $result[$key]['language'] = $languageStr;
            $row_id++;
            foreach ($item as $val) {
                $result[$key]['service'] = $val['service'];
                $result[$key]['app_name'] = Helper::getAppName($val['app_id']);
                $result[$key]['admin'] = isset($staffUserinfos[$val['service_uid']]) ?
                    $staffUserinfos[$val['service_uid']]['user_name'] : '';
                    // $service_names[$val['service_uid']] ? $service_names[$val['service_uid']] : $val['service_uid'];

                if (in_array($val['reason'], ['ok', 'user_no_reply', 'no_answer'])) {
                    $result[$key]['man_over_sum'] += 1;
                    $result[$key][$val['reason'] . '_sum'] += 1;
                    $service_time_diff = $val['end_time'] - $val['service_start'];

                    $p90_time['p90_over_time'][] = $service_time[] = $service_time_diff;
                    $p90_time[$val['service']]['p90_over_time'][] = $service_time_diff;
                    if ($val['reply_start'] > 0) {
                        $response_time = $val['reply_start'] - $val['user_start'];
                        $solve_time = $val['end_time'] - $val['reply_start'];
                        $p90_time['p90_response_time'][] = $first_response_time[] = $response_time;
                        $p90_time['p90_solve_time'][] = $ave_solve_time[] = $solve_time;
                        $p90_time[$val['service']]['p90_response_time'][] = $response_time;
                        $p90_time[$val['service']]['p90_solve_time'][] = $solve_time;
                    }
                }

                if (in_array(
                    $val['reason'],
                    ['system_service_timeout', 'system_user_timeout']
                )) {
                    $result[$key]['auto_over_sum'] += 1;
                    $result[$key][$val['reason'] . '_sum'] += 1;
                    continue;//下面3个平均值不计算自动超时情况
                }
            }
            if ($service_time) {
                $result[$key]['p90_over_time'] = Helper::calP90($service_time);
                $result[$key]['ave_man_over_sum'] = intval(array_sum($service_time) / count($service_time));
                $result[$key]['p90_ave_over_time'] = Helper::calP90Ave($result[$key]['p90_over_time'], $service_time);
            }

            if ($first_response_time) {
                $result[$key]['p90_response_time'] = Helper::calP90($first_response_time);
                $result[$key]['p90_ave_response_time'] = Helper::calP90Ave($result[$key]['p90_response_time'], $first_response_time);
                $result[$key]['ave_first_response_time'] = intval(array_sum($first_response_time) / count($first_response_time));
            }
            $result[$key]['p90_solve_time'] = Helper::calP90($ave_solve_time);
            $result[$key]['ave_solve_time'] = $result[$key]['ave_man_over_sum'] - $result[$key]['ave_first_response_time'];
            $result[$key]['p90_ave_solve_time'] = Helper::calP90Ave($result[$key]['p90_solve_time'], $ave_solve_time);
        }
        unset($service);

        //汇总
        $count = count($result);
        $tmpData = array();
        foreach ($result as $item) {
            foreach ($item as $key => $value) {
                if (!in_array($key, $staticField)) {
                    continue;
                }
                if (strpos($key, 'p90_ave') !== false) {
                    continue;
                }
                $tmpData[$key] = array_sum(array_column($result, $key));
                //其他的求和，平均时间的需要求和再平均
                if (strpos($key, 'ave') !== false) {
                    $_all_key = $this->_getP90Key($key);
                    $tmpData[$key] = !empty($p90_time[$_all_key]) ? intval(array_sum($p90_time[$_all_key]) / count($p90_time[$_all_key])) : 0;
                }
                if (strpos($key, 'p90') !== false && isset($p90_time[$key])) {
                    $tmpData[$key] = Helper::calP90($p90_time[$key]);
                    $p90_ave_key = str_replace('p90', 'p90_ave', $key);
                    if (!isset($tmpData[$p90_ave_key])) {
                        $tmpData[$p90_ave_key] = Helper::calP90Ave($tmpData[$key], $p90_time[$key]);
                    }
                }
            }
        }

        //分通道汇总
        $row_index = 2;
        $serviceSum = array();
        foreach ($result as $item) {
            if (!isset($serviceSum[$item['service']]['count'])) {
                $serviceSum[$item['service']]['count'] = 0;
            }
            $serviceSum[$item['service']]['count'] += 1;
            foreach ($item as $key => $value) {
                if (in_array($key, ['admin', 'app_name', 'service', 'p90_ave_solve_time', 'p90_ave_response_time', 'p90_ave_over_time', 'language'])) {
                    continue;
                }
                if (!isset($serviceSum[$item['service']][$key])) {
                    $serviceSum[$item['service']][$key] = 0;
                }
                $serviceSum[$item['service']][$key] += $value;
            }
        }
        foreach ($serviceSum as $serviceId => &$service) {
            $service['admin'] = '-';
            foreach ($service as $key => &$value) {
                if (strpos($key, 'p90_ave') !== false) {
                    continue;
                }
                if (strpos($key, 'ave') !== false) {
                    $_key = $this->_getP90Key($key);
                    $value = isset($p90_time[$serviceId]) && $p90_time[$serviceId] && $p90_time[$serviceId][$_key] ? intval(array_sum($p90_time[$serviceId][$_key]) / count($p90_time[$serviceId][$_key])) : 0;
                }
                if (strpos($key, 'p90') !== false && isset($p90_time[$serviceId][$key])) {
                    $value = Helper::calP90($p90_time[$serviceId][$key]);
                    $p90_ave_key = str_replace('p90', 'p90_ave', $key);
                    $service[$p90_ave_key] = Helper::calP90Ave($value, $p90_time[$serviceId][$key]);
                }
            }
            $result[$serviceId . '汇总'] = array_merge($service, array('service' => $serviceId . '汇总', 'row_id' => $row_index));
            $row_index++;
        }
        unset($p90_time);

        $result['1000000'] = array_merge($tmpData, array('service' => '1000000', 'row_id' => 1));
        $result = array_column($result, null, 'row_id');
        ksort($result);
        $result = array_values($result);

        foreach ($result as &$finalV) {
            $finalV['service_name'] = isset(XssAutoService::$manualChatServiceConfig[$finalV['service']]) ?
                XssAutoService::$manualChatServiceConfig[$finalV['service']] : '-';
            $finalV['service'] = $finalV['service'] == '1000000' ? '汇总' : $finalV['service'];
            $finalV['language'] = isset($finalV['language']) && $finalV['language'] != 0 ? $finalV['language'] : '-';
        }

        //导出的时候使用
        $cache = new StatisticsManualChatServiceCache();

        $adminUid = Helper::getSystemUid();
        $cache->setex($adminUid, $cache->getExpireTime(), serialize($result));

        
       
        return [
            'total' => count($result),
            'data' => $result,
        ];
    }

    private function _getP90Key($key)
    {
        $p90Key = $key;
        switch ($key) {
            case 'ave_solve_time':
                $p90Key = 'p90_solve_time';
                break;
            case 'ave_man_over_sum':
                $p90Key = 'p90_over_time';
                break;
            case 'ave_first_response_time':
                $p90Key = 'p90_response_time';
                break;
            default:
                break;
        }
        return $p90Key;
    }
}
