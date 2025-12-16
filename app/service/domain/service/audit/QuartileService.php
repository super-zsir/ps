<?php

namespace Imee\Service\Domain\Service\Audit;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsKanbanQuartileCache;
use Imee\Models\Xss\CsmsKanbanQuartileNew;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Service\Domain\Service\Csms\Traits\TaskTrait;

class QuartileService
{
    use TaskTrait;
    use SingletonTrait;
    /**
     * 获取p90和p90平均值
     * @param array $data
     * @return int[]|string[]
     */
    public function getQuartile(array $data)
    {
        $force = $data['force'] ?? false;
        unset($data['force']);
        $refresh = $data['refresh'] ?? false;
        unset($data['refresh']);
        // 老格式清洗
        $ok = $this->oldTransfer($data);
        if (!$ok) {
            return ['p90' => 0, 'ave_p90' => 0];
        }
        // 缓存
        $filterData = $this->filterConditionKey(self::filter($data));
        $key = $this->makeKey($filterData);
        $cache = $this->getCache($key);
        if ($cache && !$refresh) {
            return ['p90' => $cache['p90'], 'ave_p90' => $cache['p90_ave']];
        }
        list($condition, $filterCondition) = $this->filterCondition($data);
        $sign = $this->signCondition($condition, $force);
        if (!$sign) {
            // 没有符合当前设定的条件，不允许计算p90
            return [0, 0];
        }
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        list($dayP90, $dayP90Ave) = $this->dayStrategy($start, $end, $condition, $filterCondition, $refresh);
        // 存缓存
        if ($start < time() || $end < time()) {
            $this->setCache($key, $dayP90, $dayP90Ave);
        }
        return ['p90' => $dayP90, 'ave_p90' => $dayP90Ave];
    }

    /**
     * 检索策略
     * @param $start
     * @param $end
     * @param $condition
     * @param $filterCondition
     * @return int[]|string[]
     */
    protected function dayStrategy($start, $end, $condition, $filterCondition, $refresh = false)
    {
        // 是否存在两个或以上的单值检索
        $count = $this->singleConditionCount($condition);
        $datelines = $this->getDay($start, $end);
        $p90List = [];
        $p90AveList = [];
        $relate = [];
        if (!$refresh) {
            $cache = $this->tryCache($datelines, $condition, $filterCondition, $relate);
        }
        $newDateline = [];
        foreach ($datelines as $item) {
            $keyCache = isset($relate[$item]) ? ($cache[$relate[$item]] ?? []) : [];
            if ($keyCache) {
                $p90List[] = ['value' => $keyCache['p90'], 'total' => $keyCache['p90_total']];
                $p90AveList[] = ['value' => $keyCache['p90_ave'], 'total' => $keyCache['p90_ave_total']];
            } else {
                // 未获取到缓存
                $newDateline[] = $item;
            }
        }
        if ($newDateline) {
            if ($count >= 3) {
                // 两个以上的单次检索最多可一个月
                $chunkDate = array_chunk($newDateline, 31);
                foreach ($chunkDate as $item) {
                    list($dayP90, $dayP90Ave, $totalP90, $totalP90Ave) = $this->singleDay($item, $condition, $filterCondition, $refresh);
                    $p90List[] = ['value' => $dayP90, 'total' => $totalP90];
                    $p90AveList[] = ['value' => $dayP90Ave, 'total' => $totalP90Ave];
                }
            } else {
                // 单个等值的按天查询
                foreach ($newDateline as $dateline) {
                    list($dayP90, $dayP90Ave, $totalP90, $totalP90Ave) = $this->singleDay([$dateline], $condition, $filterCondition, $refresh);
                    $p90List[] = ['value' => $dayP90, 'total' => $totalP90];
                    $p90AveList[] = ['value' => $dayP90Ave, 'total' => $totalP90Ave];
                }
            }
        }
        $dayP90 = $this->calDayP90($p90List);
        $dayP90Ave = $this->calDayP90($p90AveList);
        return [$dayP90, $dayP90Ave];
    }

    /**
     * 批量获取日条件下缓存
     * @param array $datelines
     * @param $condition
     * @param $filterCondition
     * @return array
     */
    public function tryCache(array $datelines, $condition, $filterCondition, &$relate = [])
    {
        $keys = [];
        foreach ($datelines as $dateline) {
            $condition[] = ['dateline', 'in', [$dateline]];
            $keyCond = $this->filterConditionKey($filterCondition);
            $keyCond = array_merge($keyCond, $condition);
            $key = $this->makeKey($keyCond);
            $keys[] = $key;
            $relate[$dateline] = $key;
        }
        if ($keys) {
            $cache = $this->getCacheBatch($keys);
            if ($cache) {
                return array_column($cache, null, 'key');
            }
        }
        return [];
    }

    /**
     * 单值检索数量
     * @param array $condition
     * @return int
     */
    private function singleConditionCount(array $condition): int
    {
        $count = 0;
        foreach ($condition as $item) {
            if (isset($item['1'])) {
                if ($item['1'] == '=') {
                    $count++;
                    continue;
                } elseif ($item['1'] == 'in' && isset($item['2']) && is_array($item['2'])) {
                    if (count(array_filter($item['2'])) == 1) {
                        $count++;
                        continue;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 组装请求参数
     * @param array $data
     * @return array
     */
    protected function filterCondition(array $data)
    {
        $choiceId = $data['choice_id'] ?? '';
        $admin = $data['admin'] ?? '';
        $choiceType = $data['choice_type'] ?? '';
        $jobNum = $data['job_num'] ?? '';
        $type = $data['type'] ?? '';
        $area = $data['area'] ?? '';
        $appId = $data['app_id'] ?? '';
        $condition = [];
        if ($choiceId) {
            if (is_array($choiceId)) {
                $filterData = array_values(array_filter($choiceId));
                if (count($filterData) == 1) {
                    $condition[] = ['choice_id', '=', $filterData[0]];
                } else {
                    $condition[] = ['choice_id', 'in', $filterData];
                }
            } else {
                $condition[] = ['choice_id', '=', $choiceId];
            }
        }
        if ($admin) {
            if (is_array($admin)) {
                $filterData = array_values(array_filter($admin));
                if (count($filterData) == 1) {
                    $condition[] = ['admin', '=', $filterData[0]];
                } else {
                    $condition[] = ['admin', 'in', $filterData];
                }
            } else {
                $condition[] = ['admin', '=', $admin];
            }
        }
        $filterCondition = [
            'choice_type' => $choiceType,
            'job_num' => $jobNum,
            'type' => $type,
            'area' => $area,
            'app_id' => $appId,
        ];
        $filterCondition = self::filter($filterCondition);
        // 单条件加入筛选条件
        if ($filterCondition) {
            foreach ($filterCondition as $k => $item) {
                if (!in_array($k, ['job_num', 'area', 'app_id', 'type'])) {
                    // 区分度不高的不加入
                    continue;
                }
                if (!is_array($item)) {
                    $condition[] = [$k, '=', $item];
                    unset($filterCondition[$k]);
                } else {
                    $item = array_values(array_filter($item));
                    if (count($item) == 1) {
                        $condition[] = [$k, '=', $item[0] ?? ''];
                        unset($filterCondition[$k]);
                    }
                }
            }
        }
        return [$condition, $filterCondition];
    }

    /**
     * 组装key参数
     * @param array $data
     * @return array
     */
    protected function filterConditionKey(array $data)
    {
        $condition = [];
        if ($data) {
            foreach ($data as $key => $datum) {
                if (is_array($datum)) {
                    $filterData = array_values(array_filter($datum));
                    if (count($filterData) == 1) {
                        $condition[] = [$key, '=', $filterData[0] ?? ''];
                    } else {
                        $condition[] = [$key, 'in', $filterData];
                    }
                } else {
                    $condition[] = [$key, '=', $datum];
                }
            }
        }
        return $condition;
    }

    /**
     * 获取开始时间和结束时间中的日时间戳
     * @param int $start
     * @param int $end
     * @return array
     */
    public function getDay(int $start, int $end): array
    {
        $day = [];
        $i = 0;
        while ($i < 100) {
            $i++;
            $day[] = $start;
            $start = $start + 86400;
            if ($start >= $end) {
                break;
            }
        }
        return $day;
    }

    /**
     * 单日的p90计算
     * @param array $dateline
     * @param array $condition
     * @param array $filterCondition
     * @return array
     */
    public function singleDay(array $dateline, array $condition = [], array $filterCondition = [], $force = false)
    {
        // 单计算
        $canCache = true;
        foreach ($dateline as $cDateline) {
            if ($cDateline >= time() - 86400) {
                $canCache = false;
            }
        }
        $condition[] = ['dateline', 'in', $dateline];
        $keyCond = $this->filterConditionKey($filterCondition);
        $keyCond = array_merge($keyCond, $condition);
        $key = $this->makeKey($keyCond);
        $cache = $this->getCache($key);
        if ($cache && !$force) {
            return [$cache['p90'], $cache['p90_ave'], $cache['p90_total'], $cache['p90_ave_total']];
        }
        $sign = $this->signCondition($condition);
        if (!$sign) {
            return [0, 0, 0, 0];
        }
        $generator = CsmsKanbanQuartileNew::getGeneratorListByWhere($condition, 'spend_time, choice_type, job_num, type, area, app_id', 20000, 'spend_time');
        usleep(10000);
        $p90List = [];
        $p90AveList = [];
        $totalP90 = 0;
        $totalP90Ave = 0;
        foreach ($generator as $list) {
            if (empty($list)) {
                continue;
            }
            $conditionList = $this->filterGenerator($list, $filterCondition);
            // 计算当前批次单p90和p90ave
            list($p90, $p90Ave, $p90Number, $p90AveNumber) = $this->calList($conditionList);
            $p90List[] = ['value' => $p90, 'total' => $p90Number];
            $p90AveList[] = ['value' => $p90Ave, 'total' => $p90AveNumber];
            $totalP90 += $p90Number;
            $totalP90Ave += $p90AveNumber;
        }
        $dayP90 = $this->calDayP90($p90List);
        $dayP90Ave = $this->calDayP90($p90AveList);
        // 设置缓存
        if ($canCache) {
            $this->setCache($key, $dayP90, $dayP90Ave, $totalP90, $totalP90Ave);
        }
        return [$dayP90, $dayP90Ave, $totalP90, $totalP90Ave];
    }

    /**
     * 列表中不符合条件的去除
     * @param array $list
     * @param array $filterCondition
     * @return array
     */
    protected function filterGenerator(array $list, array $filterCondition)
    {
        // 遍历列表排除不符合筛选条件的列
        if (empty($list)) {
            return [];
        }
        $res = [];
        foreach ($list as $item) {
            if ($filterCondition) {
                foreach ($filterCondition as $key => $value) {
                    if (is_array($value)) {
                        if (!in_array($item[$key], $value)) {
                            continue;
                        }
                    } else {
                        if ($item[$key] != $value) {
                            continue;
                        }
                    }
                    $res[] = $item['spend_time'];
                }
            } else {
                $res[] = $item['spend_time'];
            }
        }
        return $res;
    }

    /**
     * 计算一个列表的p90和p90平均
     * @param array $list
     * @return array|int[]
     */
    protected function calList(array $list): array
    {
        // 计算列表中p90和p90ave
        if (empty($list)) {
            return [0, 0, 0, 0];
        }
        $total = count($list);
        sort($list);
        $one = ceil($total * 0.9);
        $p90 = $list[$one - 1] ?? 0;
        $length = $one > 1 ? $one - 1 : 0;
        $p90Ave = 0;
        if ($length > 0) {
            $p90Ave = sprintf('%.2f', array_sum(array_slice($list, 0, $length)) / $length);
        }
        return [$p90, $p90Ave, $total, $one];
    }

    /**
     * 根据权重获取均值
     * @param array $list
     * @return int|string
     */
    private function calDayP90(array $list)
    {
        if (empty($list)) {
            return 0;
        }
        $totalValue = 0;
        $total = 0;
        foreach ($list as $item) {
            $totalValue += $item['value'] * $item['total'];
            $total += $item['total'];
        }
        return $total > 0 ? sprintf('%.2f', $totalValue / $total) : 0;
    }

    /**
     * 根据条件生成md5唯一key
     * @param array $condition
     * @return string
     */
    protected function makeKey(array $condition)
    {
        $condKey = $this->getCondKey($condition);
        ksort($condKey);
        return md5(json_encode($condKey));
    }

    /**
     * 生成key序列
     * @param array $condition
     * @return array
     */
    private function getCondKey(array $condition)
    {
        $res = [];
        foreach ($condition as $item) {
            switch ($item[1]) {
                case '=':
                    $res[$item[0]] = $item[2];
                    break;
                case '>':
                    $res[$item[0].'_lg'] = $item[2];
                    break;
                case '>=':
                    $res[$item[0].'_elg'] = $item[2];
                    break;
                case '<':
                    $res[$item[0].'_sg'] = $item[2];
                    break;
                case '<=':
                    $res[$item[0].'_esg'] = $item[2];
                    break;
                case 'in':
                    $res[$item[0].'_array'] = $item[2];
                    break;
                default:
                    break;
            }
        }
        return $res;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getCache(string $key)
    {
        return CsmsKanbanQuartileCache::findOneByWhere([
            ['key', '=', $key]
        ]);
    }

    /**
     * @param array $keys
     * @return array
     */
    public function getCacheBatch(array $keys)
    {
        return CsmsKanbanQuartileCache::getListByWhere([
            ['key', 'in', $keys]
        ]);
    }

    /**
     * 设置缓存
     * @param string $key
     * @param int $p90
     * @param int $p90Ave
     * @param int $totalP90
     * @param int $totalP90Ave
     * @return void
     */
    public function setCache(string $key, int $p90 = 0, int $p90Ave = 0, int $totalP90 = 0, int $totalP90Ave = 0)
    {
        $list = CsmsKanbanQuartileCache::getListByWhere([
            ['key', '=', $key]
        ], 'id');
        $ids = array_column($list, 'id');
        if ($ids) {
            CsmsKanbanQuartileCache::deleteByWhere([
                ['id', 'in', $ids]
            ]);
        }
        CsmsKanbanQuartileCache::addBatch([[
            'key' => $key,
            'p90' => $p90,
            'p90_ave' => $p90Ave,
            'p90_total' => $totalP90,
            'p90_ave_total' => $totalP90Ave,
            'dateline' => time(),
        ]], 'INSERT', ['p90', 'p90_ave', 'p90_total', 'p90_ave_total']);
    }

    /**
     * 验证是否满足计算p90的条件
     * @param array $condition
     * @param bool $force
     * @return bool
     */
    public function signCondition(array $condition, bool $force = false)
    {
        if ($force) {
            // 单日的数据 通过
            return true;
        }
        if (empty($condition)) {
            // 不许没有条件
            return false;
        }
        foreach ($condition as $item) {
            $key = $item[0] ?? '';
            $value = $item[2] ?? '';
            switch ($key) {
                case 'choice_id':
                    // 审核项
                case 'admin':
                    // 审核人员
                case 'dateline':
                    // 日
                    if (!is_array($value) && !empty($value)) {
                        // 携带单个审核人员/审核项 通过
                        return true;
                    }
                    if (is_array($value)) {
                        // 携带单个审核人员/审核项 通过
                        $value = array_filter($value);
                        if (count($value) == 1) {
                            return true;
                        }
                        if ($key == 'choice_id') {
                            // 多审核项 审核项标识相同
                            $choiceList = CsmsChoice::getListByWhere([
                                ['id', 'in', $value]
                            ]);
                            if (count(array_unique(array_column($choiceList, 'choice'))) == 1) {
                                return true;
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return false;
    }

    /**
     * 老数据格式转换
     * @param array $data
     * @return bool
     */
    public function oldTransfer(array &$data)
    {
        foreach ($data as $key => $datum) {
            switch ($key) {
                case 'audit_item':
                    // 审核项
                    $checkAll = '';
                    if (is_array($datum)) {
                        $datum = array_values(array_filter($datum));
                        if (count($datum) == 1) {
                            $checkAll = $datum[0] ?? '';
                        }
                        $auditItems = $datum;
                    } else {
                        // 客服审核项和审核审核项
                        $checkAll = $datum;
                        $auditItems = [$datum];
                    }
                    if (empty($datum)) {
                        $data['choice_id'] = '';
                        unset($data['audit_item']);
                        return true;
                    }
                    if ($checkAll == 'audit_choice') {
                        $data['choice_type'] = 1;
                        unset($data['audit_item']);
                        return true;
                    }
                    if ($checkAll == 'kefu_choice') {
                        $data['choice_type'] = 2;
                        unset($data['audit_item']);
                        return true;
                    }
                    $choiceIds = CsmsChoice::getListByWhere([
                        ['choice', 'in', $auditItems]
                    ], 'id');
                    $choiceIds = array_column($choiceIds, 'id');
                    $data['choice_id'] = $choiceIds ?: '';
                    unset($data['audit_item']);
                    break;
                case 'verify_type':
                    $item = $datum;
                    unset($data[$key]);
                    if (is_array($item) || $item != 'op') {
                        return false;
                    }
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    /**
     * 统计日期区间的审核耗时
     * @param $start
     * @param $end
     * @return void
     */
    public function quatileScript($start, $end)
    {
        $day = $this->getDay($start, $end);
        foreach ($day as $item) {
            $startItem = $item - 6 * 86400;
            $endItem = $item + 86400;
            $condition = [
                ['dateline', '>=', $startItem],
                ['dateline', '<', $endItem],
            ];
            $generator = CsmsAudit::getGeneratorListByWhere($condition, '*', 10000);
            $insert = [];
            $i = 0;
            $m = 0;
            foreach ($generator as $list) {
                if ($list) {
                    foreach ($list as $value) {
                        $i++;
                        $this->isValueOk($value, $item, $insert);
                        if ($i >= 2000 && !empty($insert)) {
                            $m++;
                            $insert = $this->insertTodo($insert);
                            CsmsKanbanQuartileNew::addBatch($insert);
                            $insert = [];
                            usleep(10000);
                            $i = 0;
                        }
                    }
                }
            }
            if ($insert) {
                $insert = $this->insertTodo($insert);
                CsmsKanbanQuartileNew::addBatch($insert);
                $insert = [];
                usleep(100000);
            }
            $date = date('Y-m-d', $item);
            echo "{$date}日完成\n";
        }
    }

    /**
     * @param $value
     * @param $day
     * @param $quartile
     * @return void
     */
    private function isValueOk($value, $day, &$quartile)
    {
        // 初审环节数据（初审人审）
        $dateline = 0;
        if ($value['op_dateline'] >= $day && $value['op_dateline'] < $day + 86400 && $value['op'] < 9000) {
            $dateline = $value['op_dateline'] ?? 0;
            $admin = $value['op'] ?? 0;
            $spendTime = max($value['op_dateline'] - $value['dateline'], 0);
        }
        if ($dateline) {
            $quartile[] = $this->addQuartile($value, $dateline, $admin, $spendTime);
        }

        // 当前数据属于人工初审数据和复审环节数据暂未开放
//        $dateline = 0;
//        if ($value['op_dateline2'] >= $day && $value['op_dateline2'] < $day + 86400 && $value['op2'] < 9000) {
//            $dateline = $value['op_dateline2'] ?? 0;
//            $admin = $value['op2'] ?? 0;
//            $spendTime = max($value['op_dateline2'] - $value['dateline'], 0);
//        }
//        if ($dateline) {
//            $quartile[] = $this->addQuartile($value, $dateline, $admin, $spendTime);
//        }
        // 当前数据属于人工质检数据暂未开放
//        $dateline = 0;
//        if ($value['op_dateline3'] >= $day && $value['op_dateline3'] < $day + 86400 && $value['op3'] < 9000) {
//            $dateline = $value['op_dateline3'] ?? 0;
//            $admin = $value['op3'] ?? 0;
//            $spendTime = max($value['op_dateline3'] - $value['dateline'], 0);
//        }
//        if ($dateline) {
//            $quartile[] = $this->addQuartile($value, $dateline, $admin, $spendTime);
//        }
    }

    /**
     * @param $value
     * @param $dateline
     * @param $admin
     * @param $spendTime
     * @return array
     */
    private function addQuartile($value, $dateline, $admin, $spendTime)
    {
        return [
            'choice_id' => $value['choice'] ?? 0,
            'choice_type' => 1,
            'dateline' => strtotime(date('Y-m-d', $dateline)),
            'admin' => $admin,
            'job_num' => '',
            'type' => self::getType($value),
            'area' => $value['area'] ?? 0,
            'app_id' => $value['app_id'] ?? 0,
            'spend_time' => $spendTime,
            'create_time' => time(),
        ];
    }

    /**
     * @param $data
     * @return array
     */
    private function insertTodo($data)
    {
        // admin求job_num
        $admins = array_column($data, 'admin');
        $user = CmsUser::getListByWhere([
            ['user_id', 'in', $admins]
        ]);
        $user = array_column($user, 'job_num', 'user_id');
        // choice转choice_id
        $choice = array_column($data, 'choice_id');
        $cList = CsmsChoice::getListByWhere([
            ['choice', 'in', $choice]
        ]);
        $cList = array_column($cList, null, 'choice');

        foreach ($data as $k => &$i) {
            if (empty($i['choice_id'])) {
                unset($data[$k]);
            }
            if (!isset($cList[$i['choice_id']])) {
                unset($data[$k]);
            }
            $i['job_num'] = $user[$i['admin']] ?? '';
            $i['choice_id'] = $cList[$i['choice_id']]['id'] ?? 0;

            $extra = $cList[$i['choice_id']]['extra'] ?? "";
            $extra = @json_decode($extra, true);
            if (isset($extra['audit_type']) && $extra['audit_type'] == 'kefu') {
                $i['choice_type'] = 2;
            }
        }
        return array_values($data);
    }

    /**
     * @param $start
     * @param $end
     * @return void
     */
    public function deleteQuartile($start, $end)
    {
        $days = $this->getDay($start, $end);
        $generator = CsmsKanbanQuartileNew::getGeneratorListByWhere([
            ['dateline', 'in', $days],
        ], 'id', 20000, 'spend_time');
        foreach ($generator as $list) {
            $ids = array_column($list, 'id');
            if ($ids) {
                $chunkIds = array_chunk($ids, 2000);
                foreach ($chunkIds as $cIds) {
                    CsmsKanbanQuartileNew::deleteByWhere([
                        ['id', 'in', $cIds],
                        ['source', '=', 1],
                    ]);
                }
            }
        }
    }

    /**
     * 缓存单人/单审核项/单日p90计算
     * @param int $start
     * @param int $end
     * @return void
     */
    public function beforeCache(int $start, int $end = 0, $type = '')
    {
        if (!$start) {
            return ;
        }
        $day = $this->getDay($start, $end);
        $condition = [];
        $filterCondition = [];
        $users = CsmsUserChoice::getListByWhere(array(
            ['state', '=', 1]
        ), 'distinct user_id as user_id');
        $choice = CsmsChoice::getListByWhere(array(
            ['state', '=', 1]
        ), 'id');
        $choice = array_column($choice, 'id');
        foreach ($day as $dateline) {
            if ($type == 'staff' || !$type) {
                // 单员工
                $users = array_column($users, 'user_id');
                foreach ($users as $user) {
                    $condition[] = ['admin', '=', $user];
                    $this->singleDay([$dateline], $condition, $filterCondition, true);
                    $condition = [];
                }
            }
            if ($type == 'choice' || !$type) {
                // 单审核项
                foreach ($choice as $item) {
                    $condition[] = ['choice_id', '=', $item];
                    $this->singleDay([$dateline], $condition, $filterCondition, true);
                    $condition = [];
                }
            }
            if ($type == 'day' || !$type) {
                // 单日
                $this->singleDay([$dateline], $condition, $filterCondition, true);
            }
            if ($type == 'choice_area' || !$type) {
                // 单审核项大区
                foreach ($choice as $value) {
                    foreach (array_keys(XsBigarea::$_bigAreaMap) as $item) {
                        $condition[] = ['choice_id', '=', $value];
                        $condition[] = ['area', '=', $item];
                        $this->singleDay([$dateline], $condition, $filterCondition, true);
                        $condition = [];
                    }
                }
            }
            if ($type == 'admin_choice_type') {
                // 单员工单审核项单类型
                foreach ($users as $i) {
                    foreach ($choice as $value) {
                        foreach (['text', 'image', 'audio', 'video'] as $item) {
                            $condition[] = ['admin', '=', $i];
                            $condition[] = ['choice_id', '=', $value];
                            $condition[] = ['type', '=', $item];
                            $this->singleDay([$dateline], $condition, $filterCondition, true);
                            $condition = [];
                        }
                    }
                }
            }
            if ($type == 'choice_type' || !$type) {
                // 单审核项单类型
                foreach ($choice as $value) {
                    foreach (['text', 'image', 'audio', 'video'] as $item) {
                        $condition[] = ['choice_id', '=', $value];
                        $condition[] = ['type', '=', $item];
                        $this->singleDay([$dateline], $condition, $filterCondition, true);
                        $condition = [];
                    }
                }
            }
            $showDay = date('Y-m-d', $dateline);
            echo "finish{$showDay}\n";
        }
    }
}