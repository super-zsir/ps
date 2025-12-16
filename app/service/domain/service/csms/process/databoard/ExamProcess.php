<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Comp\Common\Fixed\Utility;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Redis\AdminRedis;
use Imee\Models\Xss\CsmsChoice;
use Imee\Models\Xss\CsmsKanbanAuditCount;
use Imee\Models\Xss\CsmsKanbanQuartile;
use Imee\Models\Xss\CsmsKanbanQuartileMonth;
use Imee\Models\Xss\CsmsUserChoice;
use Imee\Models\Xss\CsmsVerifyKanbanDetail;
use Imee\Service\Domain\Service\Audit\QuartileService;
use Imee\Service\Domain\Service\Csms\Context\Databoard\StaffListContext;
use Imee\Service\Domain\Service\Csms\Exception\DataboardDbException;
use Imee\Service\Domain\Service\Csms\Task\KanbanService;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;
use Imee\Service\Helper;

/**
 * 业务数据看板
 */
class ExamProcess
{
    use UserInfoTrait;
    const STAFF_BOARD = 'staffBoard';
    const EXAM_ITEM = 'examItem';
    const SYSTEM_OP = 9999;
    const SYSTEM_OP_NAME = '模型';
    public $users = [];
    public $choices = [];

    /**
     * 员工看板
     * @param StaffListContext $context
     * @return array
     */
    public function userList(StaffListContext $context)
    {
        try {
            $condition = $this->conditionFilter($context);
            $condition['orderBy'] = $condition['orderBy'] ?? 'xs.admin';
            $condition['columns'] = 'xs.admin,xs.audit_item,xs.type,sum(xs.audited) as audited,sum(xs.pass_num) as pass_num,
            sum(xs.refuse) as refuse,sum(xs.refuse_ten) as refuse_ten,sum(xs.wrong_pass) as wrong_pass,sum(xs.wrong_refuse) as wrong_refuse,sum(xs.audit_time) as audit_time,
            sum(xs.refuse_time) as refuse_time,sum(xs.audit_ten) as audit_ten';
            if ($context->groupBy == self::EXAM_ITEM) {
                $condition['orderBy'] = 'xs.audit_item';
                $condition['columns'] = 'xs.audit_item,xs.type,sum(xs.audited) as audited,sum(xs.pass_num) as pass_num,
            sum(xs.refuse) as refuse,sum(xs.refuse_ten) as refuse_ten,sum(xs.wrong_pass) as wrong_pass,sum(xs.wrong_refuse) as wrong_refuse,sum(xs.audit_time) as audit_time,
            sum(xs.refuse_time) as refuse_time,sum(xs.audit_ten) as audit_ten';
            }
            $condition['alias'] = 'xs';
            $list = CsmsVerifyKanbanDetail::handleList($condition);
            $total = count($list);
            $constTotal = $total;
            if ($list) {
                if ($context->groupBy == self::EXAM_ITEM) {
                    // 审核项看板统计
                    $condition['groupBy'] = 'xs.audit_item';
                    $condition['columns'] = 'xs.audit_item,sum(xs.audited) as audited,sum(xs.pass_num) as pass_num,
            sum(xs.refuse) as refuse,sum(xs.refuse_ten) as refuse_ten,sum(xs.wrong_pass) as wrong_pass,sum(xs.wrong_refuse) as wrong_refuse,sum(xs.audit_time) as audit_time,
            sum(xs.refuse_time) as refuse_time,sum(xs.audit_ten) as audit_ten';
                    $summary = CsmsVerifyKanbanDetail::handleList($condition);
                    foreach ($summary as $dic_item) {
                        $dic_item['admin'] = '/';
                        $dic_item['type'] = '全部';
                        array_unshift($list, $dic_item);
                        $total++;
                    }
                }
                if ($context->page == 1) {
                    // 员工看板统计
                    $condition['columns'] = 'sum(xs.audited) as audited,sum(xs.pass_num) as pass_num,sum(xs.refuse) as refuse,sum(xs.refuse_ten) as refuse_ten,
                    sum(xs.wrong_pass) as wrong_pass,sum(xs.wrong_refuse) as wrong_refuse,sum(xs.audit_time) as audit_time,sum(xs.refuse_time) as refuse_time,sum(xs.audit_ten) as audit_ten';
                    unset($condition['groupBy']);
                    $summary = CsmsVerifyKanbanDetail::handleList($condition);
                    $summary[0]['total'] = $this->staffAuditCount(array(
                        'audit_item' => $condition['auditItem'] ?? '',
                        'verify_type' => $condition['verify_type'] ?? '',
                        'area' => $condition['area'] ?? '',
                        'type' => $condition['type'] ?? '',
                        'start' => $condition['time_create_elg'],
                        'end' => $condition['time_create_sg'],
                    ));
                    // 日均
                    $totalDay = ceil(($condition['time_create_sg'] - $condition['time_create_elg']) / 86400);
                    $daySum = ['admin' => '/', 'audit_item' => '日均', 'dateline' => '日均', 'type' => '/'];
                    $daySum['total'] = sprintf('%.2f', $summary[0]['total'] / $totalDay);
                    $daySum['audited'] = sprintf('%.2f', $summary[0]['audited'] / $totalDay);
                    $daySum['pass_num'] = sprintf('%.2f', $summary[0]['pass_num'] / $totalDay);
                    $daySum['refuse'] = sprintf('%.2f', $summary[0]['refuse'] / $totalDay);
                    $daySum['refuse_ten'] = sprintf('%.2f', $summary[0]['refuse_ten'] / $constTotal);
                    $daySum['wrong_pass'] = sprintf('%.2f', $summary[0]['wrong_pass'] / $constTotal);
                    $daySum['wrong_refuse'] = sprintf('%.2f', $summary[0]['wrong_refuse'] / $constTotal);
                    $daySum['audit_time'] = sprintf('%.2f', $summary[0]['audit_time'] / $constTotal);
                    $daySum['refuse_time'] = sprintf('%.2f', $summary[0]['refuse_time'] / $constTotal);
                    $daySum['audit_ten'] = sprintf('%.2f', $summary[0]['audit_ten'] / $constTotal);
                    array_unshift($list, $daySum);
                    $total++;
                    // 汇总
                    $summary[0]['admin'] = '/';
                    $summary[0]['audit_item'] = '汇总';
                    $summary[0]['dateline'] = '/';
                    $summary[0]['type'] = '/';
                    array_unshift($list, $summary[0]);
                    $total++;
                } else {
                    array_unshift($list, []);
                    $total++;
                    array_unshift($list, []);
                    $total++;
                }
                $limit = $context->limit;
                $offset = ($context->page - 1) * $context->limit;
                $list = array_slice($list, $offset, $limit);
                $admins = array_filter(array_unique(array_column($list, 'admin')));
                $users = $this->getStaffBaseInfos($admins);
                $user = [];
                if (!empty($users)) {
                    $user = array_column($users, 'user_name', 'user_id');
                }
                // 审核项名称
                $choices = array_column($list, 'audit_item');
                $choice_list = CsmsChoice::handleList(array(
                    'columns' => ['choice_name', 'choice'],
                    'choice' => $choices,
                ));
                $choice_list = array_column($choice_list, 'choice_name', 'choice');
                foreach ($list as &$v) {
                    $startNow = $condition['time_create_elg'];
                    $endNow = $condition['time_create_sg'];
                    $v['admin'] = $v['admin'] ?? 0;
                    $adminNow = $this->selectOption($v['admin'], $context->admin);
                    $typeNow = $this->selectOption($v['type'] ?? '', $context->type);
                    $auditItemNow = $this->selectOption($v['audit_item'], $context->auditItem);
                    if ($v['audit_item'] != '日均') {
                        $p90List = QuartileService::getInstance()->getQuartile(array(
                            'admin' => $adminNow,
                            'type' => $typeNow,
                            'area' => $condition['area'] ?? '',
                            'audit_item' => $auditItemNow ?: $context->auditItem,
                            'verify_type' => $condition['verify_type'] ?? '',
                            'start' => $startNow,
                            'end' => $endNow,
                            'refresh' => $context->refresh,
                        ));
                    }
                    $v['total'] = $v['total'] ?? $this->staffAuditCount(array(
                            'type' => $typeNow,
                            'area' => $condition['area'] ?? '',
                            'audit_item' => $auditItemNow,
                            'verify_type' => $condition['verify_type'] ?? '',
                            'start' => $startNow,
                            'end' => $endNow,
                        ));

                    $v['p90'] = $p90List['p90'] ?? 0;
                    $v['ave_p90'] = $p90List['ave_p90'] ?? 0;
                    $v['admin_id'] = $v['admin'];
                    $v['admin'] = !is_numeric($v['admin']) ? '/' : ($user[$v['admin']] ?? ($v['admin'] == self::SYSTEM_OP ? self::SYSTEM_OP_NAME : '-'));
                    $v['dateline'] = $context->startDate . '-' . $context->endDate;
                    $v['pass_rate'] = ($v['audited'] > 0 ? sprintf('%.4f', $v['pass_num'] / $v['audited']) * 100 : 0).'%';
                    $v['questions'] = $v['refuse'] - $v['wrong_refuse'] + $v['wrong_pass'];
                    $v['unqualified'] = ($v['audited'] > 0 ? sprintf('%.5f', ($v['wrong_pass'] + $v['wrong_refuse']) / $v['audited']) : 0) * 1000 .'‰';
                    $v['problem_rate'] = $v['questions'] > 0 ? sprintf('%.2f', ($v['refuse'] - $v['wrong_refuse']) / $v['questions']) * 100 .'%' : '0%';
                    $v['avg_audit_time'] = $v['audited'] > 0 ? ceil($v['audit_time'] / $v['audited']) : 0;
                    $v['avg_refuse_time'] = $v['refuse'] > 0 ? ceil($v['refuse_time'] / $v['refuse']) : 0;
                    $v['audit_ten_rate'] = ($v['audited'] > 0 ? sprintf('%.4f', $v['audit_ten'] / $v['audited']) * 100 : 0).'%';
                    $v['refuse_ten_rate'] = ($v['audited'] > 0 ? sprintf('%.4f', $v['refuse_ten'] / $v['audited']) * 100 : 0).'%';
                    $v['audit_efficiency'] = ($v['audit_time'] > 0 ? sprintf('%.4f', $v['audited'] / $v['audit_time']) * 100 : 0).'%';
                    $v['audit_efficiency'] = ($v['audit_time'] > 0 ? sprintf('%.4f', $v['audited'] / $v['audit_time']) * 100 : 0).'%';
                    $v['audit_item'] = $choice_list[$v['audit_item']] ?? $v['audit_item'];
                    $v['type'] = $v['type'] ?? '';
                }
            }
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::STAFF_EXAM_INFO_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * @param $item
     * @param $default
     * @return mixed|string
     */
    public function selectOption($item, $default = '')
    {
        if ($item == '汇总' || $item == '平均' || $item == '/' || $item == '全部') {
            return $default;
        }
        return $item;
    }

    /**
     * 获取 审核业务数据看板 数量
     * @return int
     */
    public function userNum(array $condition)
    {
        if (isset($condition['orderBy'])) {
            unset($condition['orderBy']);
        }
        if (isset($condition['limit'])) {
            unset($condition['limit']);
        }
        if (isset($condition['offset'])) {
            unset($condition['offset']);
        }
        return CsmsVerifyKanbanDetail::handleTotal($condition);
    }

    /**
     * 渲染入参
     * @param StaffListContext $context
     * @return array
     */
    private function conditionFilter(StaffListContext $context) :array
    {
        $adminIds = '';
        if ($context->staffName) {
            $adminIds = CmsUser::handleList(array(
                'user_name_like' => $context->staffName,
                'columns' => 'user_id',
                'system_id' => CMS_USER_SYSTEM_ID
            ));
            $adminIds = array_column($adminIds, 'user_id');
        }
        $condition = array(
            'time_create_elg' => empty($context->startDate) ? strtotime(date('Y-m-d', time())) : strtotime($context->startDate),
            'time_create_sg' => empty($context->endDate) ? strtotime(date('Y-m-d', time())) + 86400 : strtotime($context->endDate) + 86400,
            'admin' => $context->admin,
            'auditItem' => $this->getAuditItem($context->auditItem),
            'actionItem' => $context->actionItem,
            'verify_type' => CsmsVerifyKanbanDetail::verifyTypeFilter($context->verifyType),
            'adminIds' => $adminIds,
            'groupBy' => $this->groupByFilter($context->groupBy),
            'orderBy' => empty($context->sort) ? '' : "{$context->sort} {$context->dir}",
            'area' => $context->area,
            'type' => $context->type,
        );
        return $this->filter($condition);
    }

    /**
     * 维度
     * @param string|null $name
     * @return string
     */
    private function groupByFilter(?string $name)
    {
        switch ($name) {
            case self::STAFF_BOARD:
                // 员工看板
                return 'xs.admin,xs.audit_item,xs.type';
            case self::EXAM_ITEM:
                // 审核项看板
                return 'xs.audit_item,xs.type';
            default:
                return '';
        }
    }

    /**
     * @param array $condition
     * @return array
     */
    private function filter(array $condition)
    {
        return array_filter($condition, function ($item) {
            if ($item === '' || $item === null || $item === ['']) {
                return false;
            }
            return true;
        });
    }

    /**
     * 获取p90
     * @param array $condition
     * @return array
     */
    public function getQuartile(array $condition)
    {
        if (!isset($condition['dateline_start']) || !isset($condition['dateline_end'])) {
            return [];
        }
        $refresh = $condition['refresh'] ?? 0;
        unset($condition['refresh']);
        $date_range = Utility::genRangeDate($condition['dateline_start'], $condition['dateline_end']);
        $condition['columns'] = ['dateline', 'spend_time'];
        $dateDayValue = [];
        $json_condition = $condition;
        unset($json_condition['dateline_start']);
        unset($json_condition['dateline_end']);

        // 获取缓存
        $redisKeys = [];
        foreach ($date_range as $item) {
            $redisKeys[] = $this->getQaKey($json_condition, $item);
        }
        $quartileData = AdminRedis::getQuartile($redisKeys);
        foreach ($date_range as $item) {
            $dayNow = strtotime($item);
            $keyNow = $this->getQaKey($json_condition, $item);
            if (isset($quartileData[$keyNow])) {
                if ($refresh) {
                    AdminRedis::delQuartile($keyNow);
                }
                $dateDayValue[$dayNow] = json_decode($quartileData, true);
                continue;
            }
            // 获取p90和p90平均
            $dayEndNow = $dayNow + 86400;
            $condition['dateline_start'] = $dayNow;
            $condition['dateline_end'] = $dayEndNow;

            $p90 = $p90_ave = 0;
            $spend_total = CsmsKanbanQuartile::handleTotal($condition);
            if ($spend_total) {
                // 获取p90
                $p90_condition = $condition;
                $p90_total = ceil($spend_total * 0.1);
                $p90_condition['limit'] = $p90_total;
                $p90_condition['columns'] = ['spend_time'];
                $p90_condition['orderBy'] = 'spend_time desc';
                $max_all = CsmsKanbanQuartile::handleList($p90_condition);
                $all_times = array_column($max_all, 'spend_time');
                $p90 = min($all_times);
                // 获取p90平均
                $p90_ave_condition = $condition;
                $p90_ave_condition['spend_time_ls'] = $p90;
                $p90_ave_condition['columns'] = ['AVG(spend_time) as spend_time'];
                $p90_ave_one = CsmsKanbanQuartile::handleOne($p90_ave_condition);
                $p90_ave = $p90_ave_one->spend_time;
            }
            $dateDayValue[$dayNow] = ['p90' => $p90, 'p90_ave' => $p90_ave];
            AdminRedis::setQuartile($keyNow, $dateDayValue[$dayNow]);
        }
        return $dateDayValue;
    }

    /**
     * 员工p90
     * @param array $data
     * @return array|int[]
     */
    public function getQuartileAdmin(array $data): array
    {
        foreach ($data as $k => $item) {
            if ($item == '汇总' || $item == '平均' || $item == '/' || $item == '全部') {
                $data[$k] = '';
            }
        }
        $admin = $data['admin'] ?? 0;
        $type = $data['type'] ?? '';
        $auditItem = $data['audit_item'] ?? '';
        $verifyType = $data['verify_type'] ?? '';
//        $dateline = $data['dateline'] ?? '';
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $area = $data['area'] ?? '';
        $refresh = $data['refresh'] ?? 0;
        if ($end > time() || $start > time()) {
            $refresh = 1;
        }
        $force = $data['force'] ?? false;
        if (!$force) {
            if ($end - $start > 86400 && empty($admin) && empty($auditItem)) {
                return ['p90' => 0, 'ave_p90' => 0];
            }
        }
        $condition = array(
//            ['dateline', '=', $dateline],
            ['type', '=', $type],
            ['dateline', '>=', $start],
            ['dateline', '<', $end],
        );
        if (is_array($area)) {
            $condition[] = ['area', 'in', $area];
        } else {
            $condition[] = ['area', '=', $area];
        }
        if (is_array($auditItem)) {
            sort($auditItem);
            $condition[] = ['audit_item', 'in', $auditItem];
        } else {
            $condition[] = ['audit_item', '=', $auditItem];
        }
        if (is_array($verifyType)) {
            $condition[] = ['verify_type', 'in', $verifyType];
        } else {
            $condition[] = ['verify_type', '=', $verifyType];
        }
        if (is_array($admin)) {
            $condition[] = ['admin', 'in', $admin];
        } elseif ($admin > 0) {
            $condition[] = ['admin', '=', $admin];
        }
        // 默认值
        if ($admin == 0) {
            // 获取全部admin
            if (!$this->users) {
                $users = CsmsUserChoice::getListByWhere(array(
                    ['state', '=', 1]
                ), 'distinct user_id as user_id');
                $this->users = array_column($users, 'user_id');
            }
            $condition[] = ['admin', 'in', $this->users];
        }
        if (empty($auditItem)) {
            if (!$this->choices) {
                $choice = CsmsChoice::getListByWhere(array(
                    ['state', '=', 1]
                ), 'choice, extra');
                $this->choices = array_column($choice, 'choice');
            }
            sort($this->choices);
            $condition[] = ['audit_item', 'in', $this->choices];
        }
        if (empty($verifyType)) {
            $condition[] = ['verify_type', '=', 'op'];
        }
        // 总数量
        $total = $this->calculateCountPersonal($condition, $refresh);
        if ($total) {
            // p90
            $p90 = $this->calculateP90Personal($condition, $total, $refresh);
            // 90平均
            $aveP90 = $this->calculateP90avePersonal($condition, $p90, $refresh);
            return ['p90' => $p90, 'ave_p90' => $aveP90];
        }
        return ['p90' => 0, 'ave_p90' => 0];
    }

    /**\
     * @param $condition
     * @param $time
     * @return string
     */
    private function getQaKey($condition, $time): string
    {
        $dayNow = strtotime($time);
        $condition['day_now'] = $dayNow;
        ksort($condition);
        return md5(json_encode($condition));
    }

    public function getAuditItem($audit)
    {
        $oldAudit = $audit;
        if (!is_array($audit)) {
            $audit = [$audit];
        }
        $audit = array_filter($audit);
        if (count($audit) == 1) {
            $res = [];
            $auditNow = array_pop($audit);
            if (!in_array($auditNow, ['audit_choice', 'kefu_choice'])) {
                return $oldAudit;
            }
            $choice = CsmsChoice::getListByWhere(array(
                ['state', '=', 1]
            ), 'choice, extra');
            if ($choice) {
                foreach ($choice as $item) {
                    $extra = json_decode($item['extra'], true);
                    if ($auditNow == 'kefu_choice') {
                        // 客服审核项
                        if (isset($extra['audit_type']) && $extra['audit_type'] == 'kefu') {
                            $res[] = $item['choice'];
                        }
                    } else {
                        if (!isset($extra['audit_type']) || $extra['audit_type'] != 'kefu') {
                            $res[] = $item['choice'];
                        }
                    }

                }
            }
            return $res;
        } else {
            return $oldAudit;
        }
    }

    /**
     * 获取条件下的进线量
     * @param array $data
     * @return int
     */
    public function staffAuditCount(array $data, $type = 'sum')
    {
        $condition = $this->commonFilterCondition($data);
        switch ($type) {
            case 'sum':
                $list = CsmsKanbanAuditCount::getListByWhere($condition, 'sum(total_num) as total_num', '', 1);
                break;
            case 'avg':
                $list = CsmsKanbanAuditCount::getListByWhere($condition, 'AVG(total_num) as total_num', '', 1);
                break;
            default:
                break;
        }
        if ($list) {
            return $list[0]['total_num'] ?? 0;
        }
        return 0;
    }

    /**
     * @param array $data
     * @return array[]
     */
    private function commonFilterCondition(array $data)
    {
        $start = $data['start'];
        $end = $data['end'];
        $auditItem = $data['audit_item'] ?? '';
        $area = $data['area'] ?? '';
        $type = $data['type'] ?? '';
        $condition = array(
            ['dateline', '>=', $start],
            ['dateline', '<', $end],
        );
        if (is_array($auditItem)) {
            if (in_array('audit_choice', $auditItem) || in_array('kefu_choice', $auditItem)) {
                $AllChoice = KanbanService::getChoices();
                if (in_array('audit_choice', $auditItem)) {
                    $auditItem = array_merge($auditItem, $AllChoice['audit_choice'] ?? []);
                }
                if (in_array('kefu_choice', $auditItem)) {
                    $auditItem = array_merge($auditItem, $AllChoice['kefu_choice'] ?? []);
                }
                $auditItem = array_values(array_unique($auditItem));
            }
            $condition[] = ['audit_item', 'in', $auditItem];
        } else {
            $condition[] = ['audit_item', '=', $auditItem];
        }
        if (is_array($area)) {
            $condition[] = ['area', 'in', $area];
        } else {
            $condition[] = ['area', '=', $area];
        }
        if (is_array($type)) {
            $condition[] = ['type', 'in', $type];
        } else {
            $condition[] = ['type', '=', $type];
        }
        return Helper::filterWhere($condition);
    }

    /**
     * @param $data
     * @return array|int[]
     */
    public function getP90($data)
    {
        $admin = $data['admin'] ?? 0;
        $type = $data['type'] ?? '';
        $auditItem = $data['audit_item'] ?? '';
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $auditItemMonth = $auditItem ?: 'all';
        if (is_array($auditItemMonth)) {
            $auditItemMonth = array_filter($auditItemMonth);
            if (count($auditItemMonth) == 1) {
                $auditItemMonth = array_pop($auditItemMonth);
            }
        }
        // 是否满足月筛选
        $monthStart = strtotime(date('Y-m-1', $start));
        if ($monthStart == $start) {
            $endMonth = strtotime('next month', $monthStart);
            if ($endMonth == $end) {
                // 月筛选
                $typeMonth = $type ?: 'all';
                $adminMonth = $admin == 0 ? 'all' : $admin;
                if (!is_array($auditItemMonth) && in_array($auditItemMonth, ['all', 'audit_choice', 'kefu_choice'])) {
                    $monthP90 = CsmsKanbanQuartileMonth::findOneByWhere(array(
                        ['month', '=', $monthStart],
                        ['audit_item', '=', $auditItemMonth],
                        ['admin', '=', $adminMonth],
                        ['type', '=', $typeMonth],
                    ));
                    return ['p90' => $monthP90['p90'] ?? 0, 'ave_p90' => $monthP90['p90_ave'] ?? 0];
                }
            }
        }
        if (!is_array($auditItemMonth) && in_array($auditItemMonth, ['all', 'audit_choice', 'kefu_choice'])) {
            //
            $AllChoice = KanbanService::getChoices();
            $data['audit_item'] = $AllChoice[$auditItemMonth] ?? $data['audit_item'];
        }
        return $this->getQuartileAdmin($data);
    }

    /**
     * @param array $condition
     * @return array
     */
    public function getCondKey(array $condition)
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
     * 计算多人的审核总量
     * @param $condition
     * @param bool $refresh
     * @return int|mixed
     */
    public function calculateCountPersonal($condition, bool $refresh = false)
    {
        $condition = Helper::filterWhere($condition);
        $condKey = $this->getCondKey($condition);
        ksort($condKey);
        $totalKey = md5(json_encode($condKey));
        $totalCount = AdminRedis::getQua($totalKey, 'count');
        if ($totalCount !== false && !$refresh) {
            return $totalCount;
        }
        $resTotal = 0;
        $admins = [];
        foreach ($condition as $k => $im) {
            if ($im[0] == 'admin') {
                $im2 = $im[2] ?? [];
                if (is_numeric($im2)) {
                    $admins = [$im2];
                } else {
                    $admins = $im2;
                }
                unset($condition[$k]);
                break;
            }
        }
        if (empty($admins) || !is_array($admins)) {
            return 0;
        }
        foreach ($admins as $admin) {
            $newCondition = $condition;
            $newCondition[] = ['admin', '=', $admin];
            $newCondition = Helper::filterWhere($newCondition);
            $condKey = $this->getCondKey($newCondition);
            ksort($condKey);
            $key = md5(json_encode($condKey));
            $totalCache = AdminRedis::getQua($key, 'count');
            if ($totalCache !== false && !$refresh) {
                $total = $totalCache;
            } else {
                $total = 0;//CsmsKanbanQuartile::getCount($newCondition);
//                AdminRedis::setQua($key, 'count', $total);
//                usleep(10000);
            }
            $resTotal += intval($total);
        }
        AdminRedis::setQua($totalKey, 'count', $resTotal);
        return $resTotal;
    }

    /**
     * @param $condition
     * @param $total
     * @param bool $refresh
     * @return int|mixed
     */
    public function calculateP90Personal($condition, $total, bool $refresh = false)
    {
        $condition = Helper::filterWhere($condition);
        $condKey = $this->getCondKey($condition);
        ksort($condKey);
        $totalKey = md5(json_encode($condKey));
        // 90分位耗时
        $totalCount = AdminRedis::getQua($totalKey, 'p90');
        if ($totalCount !== false && !$refresh) {
            return $totalCount;
        } else {
            // 90分位数量
            $ceilOne = ceil($total * 0.1);
            $maxList = [];//CsmsKanbanQuartile::getListByWhere($condition, 'id, spend_time', 'spend_time desc', 1, $ceilOne);
            $p90 = $maxList[0]['spend_time'] ?? 0;
//            AdminRedis::setQua($totalKey, 'p90', $p90);
            usleep(10000);
        }
        return $p90;
    }

    /**
     * 计算多人的p90平均
     * @param $condition
     * @param $p90
     * @param bool $refresh
     * @return int|mixed|string
     */
    public function calculateP90avePersonal($condition, $p90, bool $refresh = false)
    {
        $condition = Helper::filterWhere($condition);
        $condKey = $this->getCondKey($condition);
        ksort($condKey);
        $totalKey = md5(json_encode($condKey));
        $totalP90 = AdminRedis::getQua($totalKey, 'p90_ave');
        if ($totalP90 && !$refresh) {
            return $totalP90;
        }
        $resTotal = 0;
        $personTotalCount = 0;
        $admins = [];
        foreach ($condition as $k => $im) {
            if ($im[0] == 'admin') {
                $im2 = $im[2] ?? [];
                if (is_numeric($im2)) {
                    $admins = [$im2];
                } else {
                    $admins = $im2;
                }
                unset($condition[$k]);
                break;
            }
        }
        if (empty($admins) || !is_array($admins)) {
            return 0;
        }
        foreach ($admins as $admin) {
            $newCondition = $condition;
            $newCondition[] = ['admin', '=', $admin];
            $newCondition = Helper::filterWhere($newCondition);
            $condKey = $this->getCondKey($newCondition);
            ksort($condKey);
            $key = md5(json_encode($condKey));
            // 总耗时
            $spendTimeCache = AdminRedis::getQua($key, 'sum_spend_time');
            $countCache = AdminRedis::getQua($key, 'sum_count');
            if ($spendTimeCache !== false && $countCache !== false && !$refresh) {
                $spendTime = $spendTimeCache;
                $personalCount = $countCache;
            } else {
                $newCondition[] = ['spend_time', '<', $p90];
                $spendTimeList = [];//CsmsKanbanQuartile::getListByWhere($newCondition, 'sum(spend_time) as spend_time, count(*) as count');
                $spendTime = 0;
                $personalCount = 0;
                if ($spendTimeList) {
                    $spendTime = $spendTimeList[0]['spend_time'];
                    $personalCount = $spendTimeList[0]['count'];
                }
//                AdminRedis::setQua($key, 'sum_spend_time', $spendTime);
//                AdminRedis::setQua($key, 'sum_count', $personalCount);
                usleep(10000);
            }
            $resTotal += intval($spendTime);
            $personTotalCount += intval($personalCount);
        }
        // 计算p90平均
        if ($personTotalCount > 0) {
            $p90Ave = sprintf('%.2f', $resTotal / $personTotalCount);
        } else {
            $p90Ave = 0;
        }
        AdminRedis::setQua($totalKey, 'p90_ave', $p90Ave);
        return $p90Ave;
    }
}
