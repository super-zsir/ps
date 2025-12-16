<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Xss\CsmsVerifyKanbanDetail;
use Imee\Service\Domain\Service\Audit\QuartileService;
use Imee\Service\Domain\Service\Csms\Context\Databoard\DailyContext;
use Imee\Service\Domain\Service\Csms\Exception\DataboardDbException;
use Imee\Service\Domain\Service\Traits\UserInfoTrait;

/**
 * 业务数据看板 - 详细维度
 */
class ExamDetailProcess
{
    use UserInfoTrait;
    const EXAM_DAY_ITEM = 'examDetailItem';
    const EXAM_DAY_ITEM_AUDIT = 'examDetailItemAudit';
    const EXAM_DAY_ITEM_AUDIT_ACTION = 'examDayItemAuditAction';

    /**
     * 日维度详情统计
     * @param DailyContext $dailyContext
     * @return array
     */
    public function handleList(DailyContext $dailyContext)
    {
        $condition = $this->conditionFilter($dailyContext);
        $condition['limit'] = $dailyContext->limit;
        $condition['offset'] = $dailyContext->offset;
        $condition['columns'] = "dateline,
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num,
			sum(review) as review_num,
			sum(not_review) as not_review_num,
			sum( pass_num ) AS pass_num,
			sum( refuse ) AS refuse,
			sum( audit_time ) AS audit_time,
			sum(IF(verify_type='op',audit_time,0)) as op_audit_time";
        $list = CsmsVerifyKanbanDetail::handleList($condition);
        if ($list) {
            $pro = new ExamProcess();
            if ($dailyContext->page == 1) {
                // 汇总统计
                unset($condition['groupBy']);
                $condition['columns'] = "
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num,
			sum(review) as review_num,
			sum(not_review) as not_review_num,
			sum( pass_num ) AS pass_num,
			sum( refuse ) AS refuse,
			sum( audit_time ) AS audit_time,
			sum(IF(verify_type='op',audit_time,0)) as op_audit_time";
                $summary = CsmsVerifyKanbanDetail::handleOne($condition)->toArray();

                $summary['total'] = $pro->staffAuditCount(array(
                    'audit_item' => $dailyContext->auditItem,
                    'verify_type' => $condition['verify_type'] ?? '',
                    'area' => $condition['area'] ?? '',
                    'type' => $condition['type'] ?? '',
                    'start' => $condition['dateline_start'],
                    'end' => $condition['dateline_end'],
                ));
                // 取总数
                $total = $this->handleNum($dailyContext);
                if ($total > 0) {
                    array_unshift($list, array(
                        'dateline' => '日平均',
                        'total' => ceil($summary['total']/$total),
                        'audited' => ceil($summary['audited']/$total),
                        'op_num' => ceil($summary['op_num']/$total),
                        'op2_num' => ceil($summary['op2_num']/$total),
                        'op3_num' => ceil($summary['op3_num']/$total),
                        'review_num' => ceil($summary['review_num']/$total),
                        'not_review_num' => ceil($summary['not_review_num']/$total),
                        'pass_num' => ceil($summary['pass_num']/$total),
                        'refuse' => ceil($summary['refuse']/$total),
                        'audit_time' => ceil($summary['audit_time']/$total),
                        'op_audit_time' => ceil($summary['op_audit_time']/$total),
                    ));
                }
                $summary['dateline'] = '汇总';
                array_unshift($list, $summary);
            }
            foreach ($list as &$v) {
                if (is_numeric($v['dateline'])) {
                    $startDatelineNow = $v['dateline'];
                    $endDatelineNow = $v['dateline'] + 86400;
                    $force = true;
                } else {
                    $startDatelineNow = $condition['dateline_start'];
                    $endDatelineNow = $condition['dateline_end'];
                    $force = false;
                }
                $p90List = [];
                if ($v['dateline'] != '日平均') {
                    $p90List = QuartileService::getInstance()->getQuartile($this->filter(array(
                        'audit_item' => $condition['auditItem'] ?? '',
                        'verify_type' => $condition['verify_type'] ?? '',
                        'area' => $condition['area'] ?? '',
                        'type' => $condition['type'] ?? '',
                        'start' => $startDatelineNow,
                        'end' => $endDatelineNow,
                        'force' => $force, // 强制查询
                        'refresh' => $dailyContext->refresh,
                    )));
                }
                $v['total'] = $v['total'] ?? $pro->staffAuditCount(array(
                        'audit_item' => $condition['auditItem'] ?? '',
                        'verify_type' => $condition['verify_type'] ?? '',
                        'area' => $condition['area'] ?? '',
                        'type' => $condition['type'] ?? '',
                        'start' => $startDatelineNow,
                        'end' => $endDatelineNow,
                        'app_id' => $condition['app_id'] ?? '',
                    ));

                $v['p90'] = $p90List['p90'] ?? 0;
                $v['p90_ave'] = $p90List['ave_p90'] ?? 0;
                $v['dateline'] = is_numeric($v['dateline']) ? date('Y-m-d', $v['dateline']) : $v['dateline'];
                $v['pass_rate'] = ($v['audited'] > 0 ? sprintf('%.4f', $v['pass_num'] / $v['audited']) * 100 : 0).'%';
                $v['refuse_rate'] = ($v['audited'] > 0 ? sprintf('%.4f', $v['refuse'] / $v['audited']) * 100 : 0).'%';
                $v['avg_op_audit_time'] = $v['op_num'] > 0 ? ceil($v['op_audit_time'] / $v['op_num']) : 0;
                $v['avg_audit_time'] = $v['audited'] > 0 ? ceil($v['audit_time'] / $v['audited']) : 0;
            }
        }
        return $list;
    }

    /**
     * 日维度详情统计数量
     * @param DailyContext $dailyContext
     * @return int
     */
    public function handleNum(DailyContext $dailyContext)
    {
        $condition = $this->conditionFilter($dailyContext);
        if (isset($condition['orderBy'])) {
            unset($condition['orderBy']);
        }
        $condition['columns'] = ['dateline'];
        return CsmsVerifyKanbanDetail::handleTotal($condition);
    }

    /**
     * 日维度明细统计
     * @param DailyContext $dailyContext
     * @return array
     */
    public function handleDetailList(DailyContext $dailyContext)
    {
        try {
            $condition = $this->conditionFilter($dailyContext);
            $condition['columns'] = "dateline,audit_item,
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num";
            $list = CsmsVerifyKanbanDetail::handleDetailList($condition);
            $newList = [];
            $total = 0;
            if ($list) {
                foreach ($list as $item) {
                    $date = $item['dateline'];
                    $newList = $this->getNewList($newList, $date, $item);
                }
                // 横向渲染
                $res_list = $this->renderData($newList);
                // 统计
                $total = count($res_list);
                $summit = $this->handleSummit($condition, $total);
                $sort_arr = array_column($res_list, 'dateline');
                array_multisort($sort_arr, SORT_DESC, $res_list);
                $list = array_merge($summit, $res_list);
                $list = array_slice($list, $dailyContext->offset, $dailyContext->limit);
            }
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::DAY_DETAIL_AUDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
        return ['data' => $list, 'total' => $total];
    }

    /**
     * 获取全量统计数据
     * @param array $condition
     * @param int $total
     * @return array|void
     */
    public function handleSummit(array $condition, int $total)
    {
        $condition['columns'] = "audit_item,
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num";
        $condition['groupBy'] = 'audit_item';
        $list = CsmsVerifyKanbanDetail::handleDetailList($condition);
        if ($list) {
            $newList = [];
            $first = '汇总';
            $second = '日平均';
            foreach ($list as $item) {
                // 汇总
                $newList = $this->getNewList($newList, $first, $item);
                // 日平均
                $newList[$second]['audited'] = ceil($newList[$first]['audited']/$total);
                $newList[$second]['op_num'] = ceil($newList[$first]['op_num']/$total);
                $newList[$second]['op2_num'] = ceil($newList[$first]['op2_num']/$total);
                $newList[$second]['op3_num'] = ceil($newList[$first]['op3_num']/$total);
                $newList[$second][$item['audit_item']] = implode('/', array(
                    'audited' => ceil($item['audited']/$total),
                    'op_num' => ceil($item['op_num']/$total),
                    'op2_num' => ceil($item['op2_num']/$total),
                    'op3_num' => ceil($item['op3_num']/$total),
                ));
            }
            return $this->renderData($newList);
        }
    }

    /**
     * 数据渲染横向展示
     * @param array $newList
     * @return array
     */
    public function renderData(array $newList): array
    {
        $res_list = [];
        foreach ($newList as $date => $v) {
            $item = array(
                'dateline' => is_numeric($date) ? date('Y-m-d', $date) : $date,
                'audited' => $v['audited'],
                'op_num' => $v['op_num'],
                'op2_num' => $v['op2_num'],
                'op3_num' => $v['op3_num'],
            );
            $all_audit = AuditConstant::ALL_AUDIT;
            $all_audit = array_map(function ($audit) use ($v) {
                return $v[$audit] ?? '0/0/0/0';
            }, $all_audit);
            $item += $all_audit;
            $res_list[] = $item;
        }
        return $res_list;
    }

    /**
     * 获取某个审核项明细统计
     * @param string $auditItem
     * @return array
     */
    public function handleAuditItem(string $auditItem)
    {
        $condition['columns'] = "dateline,audit_item,action_item,
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num";
        $condition['groupBy'] = $this->groupByFilter(self::EXAM_DAY_ITEM_AUDIT_ACTION);
        $condition['auditItem'] = $auditItem;
        return CsmsVerifyKanbanDetail::handleDetailList($condition);
    }

    /**
     * 汇总
     * @param DailyContext $dailyContext
     * @return array
     */
    public function summmit(DailyContext $dailyContext)
    {
        // 汇总统计
        $condition = $this->conditionFilter($dailyContext);
        unset($condition['groupBy']);
        $condition['columns'] = "dateline,
			sum( audited ) AS audited,
			SUM(IF(verify_type='op',audited,0)) as op_num,
			SUM(IF(verify_type='op2',audited,0)) as op2_num,
			SUM(IF(verify_type='op3',audited,0)) as op3_num,
			sum(review) as review_num,
			sum(not_review) as not_review_num,
			sum( pass_num ) AS pass_num,
			sum( refuse ) AS refuse,
			sum( audit_time ) AS audit_time,
			sum(IF(verify_type='op',audit_time,0)) as op_audit_time";
        return CsmsVerifyKanbanDetail::handleDetailOne($condition);
    }

    /**
     * @param DailyContext $dailyContext
     * @return array
     */
    private function conditionFilter(DailyContext $dailyContext) :array
    {
        $adminIds = '';
        if ($dailyContext->staffName) {
            $adminIds = CmsUser::handleList(array(
                'user_name_like' => $dailyContext->staffName,
                'columns' => 'user_id'
            ));
            $adminIds = array_column($adminIds, 'user_id');
        }
        $examProcess = new ExamProcess();
        $condition = array(
            'admin' => $dailyContext->admin,
            'auditItem' => $examProcess->getAuditItem($dailyContext->auditItem),
            'actionItem' => $dailyContext->actionItem,
            'verify_type' => CsmsVerifyKanbanDetail::verifyTypeFilter($dailyContext->verifyType),
            'adminIds' => $adminIds,
            'groupBy' => $this->groupByFilter($dailyContext->groupBy),
            'orderBy' => empty($dailyContext->sort) ? '' : "{$dailyContext->sort} {$dailyContext->dir}",
            'app_id' => $dailyContext->appId,
            'is_machine' => $dailyContext->isMachine,
            'dateline_start' =>  empty($dailyContext->datelineStart) ? strtotime(date('Y-m-d', time())) : strtotime($dailyContext->datelineStart),
            'dateline_end' => empty($dailyContext->datelineEnd) ? strtotime(date('Y-m-d', time())) + 86400 : strtotime($dailyContext->datelineEnd) + 86400,
            'area' => $dailyContext->area,
            'type' => $dailyContext->type,
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
            case self::EXAM_DAY_ITEM:
                // 审核项详情看板
                return 'dateline';
            case self::EXAM_DAY_ITEM_AUDIT:
                // 审核项详情看板 - 审核项明细
                return 'dateline,audit_item';
            case self::EXAM_DAY_ITEM_AUDIT_ACTION:
                // 审核项详情看板 - 审核项明细具体审核项
                return 'dateline,action_item';
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
     * @param array $newList
     * @param $date
     * @param $item
     * @return array
     */
    public function getNewList(array $newList, $date, $item): array
    {
        $newList[$date]['audited'] = isset($newList[$date]['audited']) ? $newList[$date]['audited'] + $item['audited'] : $item['audited'];
        $newList[$date]['op_num'] = isset($newList[$date]['op_num']) ? $newList[$date]['op_num'] + $item['op_num'] : $item['op_num'];
        $newList[$date]['op2_num'] = isset($newList[$date]['op2_num']) ? $newList[$date]['op2_num'] + $item['op2_num'] : $item['op2_num'];
        $newList[$date]['op3_num'] = isset($newList[$date]['op3_num']) ? $newList[$date]['op3_num'] + $item['op3_num'] : $item['op3_num'];
        $newList[$date][$item['audit_item']] = implode('/', array(
            'audited' => $item['audited'],
            'op_num' => $item['op_num'],
            'op2_num' => $item['op2_num'],
            'op3_num' => $item['op3_num'],
        ));
        return $newList;
    }
}
