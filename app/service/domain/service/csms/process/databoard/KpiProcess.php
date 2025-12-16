<?php

namespace Imee\Service\Domain\Service\Csms\Process\Databoard;

use Imee\Exception\Audit\DataboardDbException;
use Imee\Models\Bms\BmsVerifyKanbanKpi;
use Imee\Service\Domain\Service\Csms\Context\Databoard\CommonKpiContext;
use Imee\Service\Domain\Service\Csms\Context\Databoard\KpiListContext;

/**
 * kpi管理
 */
class KpiProcess
{
    /**
     * @return array
     */
    public function getList(KpiListContext $context)
    {
        $condition = array(
            'orderBy' => 'create_time desc',
            'columns' => 'id, exam_item, total_score, arm, exam, create_time',
            'limit' => $context->limit,
            'offset' => $context->offset,
        );
        $list = BmsVerifyKanbanKpi::handleList($condition);
        if ($list) {
            foreach ($list as &$item) {
                $item['exam_item_name'] = BmsVerifyKanbanKpi::EXAM_ITEM[$item['exam_item']] ?? '无效审核项';
                $exam_text = $this->text($item['exam']);
                $item['rule'] = "总分{$item['total_score']}分;目标值{$item['arm']};{$exam_text}";
                $rule_arr = json_decode($item['exam'], true);
                $item += $rule_arr;
            }
        }
        return $list;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        $condition = array(
            'groupBy' => 'exam_item',
        );
        return BmsVerifyKanbanKpi::handleTotal($condition);
    }

    /**
     * 新增
     * @param CommonKpiContext $context
     * @return bool
     * @throws \ReflectionException
     */
    public function addKpi(CommonKpiContext $context)
    {
        $one = BmsVerifyKanbanKpi::handleOne(['exam_item' => $context->examItem]);
        if ($one) {
            DataboardDbException::throwException(DataboardDbException::KPI_ITEM_EXIST);
        }
        try {
            $condition = array(
                'exam_item' => $context->examItem,
                'total_score' => $context->totalScore,
                'arm' => $context->arm,
                'exam' => $this->merge($context),
            );
            BmsVerifyKanbanKpi::handleSave($condition);
            // 改动记录
            $exam_text = $this->text($condition['exam']);
            return "总分{$context->totalScore}分;目标值{$context->arm};{$exam_text}";
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::KPI_DB_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 编辑
     * @param CommonKpiContext $context
     * @return false|string
     * @throws \ReflectionException
     */
    public function editKpi(CommonKpiContext $context)
    {
        try {
            $condition = array(
                'id' => $context->id,
                'columns' => 'id, total_score, arm, exam, create_time',
            );
            $list = BmsVerifyKanbanKpi::handleOne($condition);
            if ($list) {
                // 修改
                $condition = array(
                    'total_score' => $context->totalScore,
                    'arm' => $context->arm,
                    'exam' => $this->merge($context),
                );
                BmsVerifyKanbanKpi::handleEdit($context->id, $condition);
                // 改动记录
                $exam_text = $this->text($condition['exam']);
                return "总分{$context->totalScore}分;目标值{$context->arm};{$exam_text}";
            }
            return false;
        } catch (\Exception $e) {
            DataboardDbException::throwException(DataboardDbException::KPI_DB_EDIT_ERROR, ['exception'=>$e->getMessage(),'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * 获取分值描述
     * @param string $rule
     * @return string
     */
    protected function text(string $rule)
    {
        $res = [];
        $array = @json_decode($rule, true);
        if (!empty($array) && is_array($array)) {
            foreach ($array as $key => $item) {
                switch ($key) {
                    case 'total_score':
                        $res[] = "总分{$item}分";
                        break;
                    case 'arm':
                        $res[] = "目标值{$item}";
                        break;
                    case 'audit_less_num':
                        if (isset($array['audit_less_score'])) {
                            $res[] = "审核量每少{$item}个，扣{$array['audit_less_score']}分";
                        }
                        break;
                    case 'audit_add_num':
                        if (isset($array['audit_add_score']) && isset($array['audit_max_add'])) {
                            $res[] = "审核量每增加{$item}个，加{$array['audit_add_score']}分，满分{$array['audit_max_add']}分";
                        }
                        break;
                        // 错审
                    case 'exam_aless':
                        $res[] = "A类错审，扣{$item}分/个";
                        break;
                    case 'exam_bless':
                        $res[] = "B类错审，扣{$item}分/个";
                        break;
                    case 'exam_cless':
                        $res[] = "C类错审，扣{$item}分/个";
                        break;
                    case 'exam_good_score':
                        $res[] = "无错审，加{$item}分";
                        break;
                        // 平均审核时长
                    case 'audit_time_less':
                        if (isset($array['audit_time_add_score']) && isset($array['audit_time_max'])) {
                            $res[] = "每低于优秀值{$item}秒，加{$array['audit_time_add_score']}分，满分{$array['audit_time_max']}分";
                        }
                        break;
                    case 'audit_time_more':
                        if (isset($array['audit_time_desc_score'])) {
                            $res[] = "每高于优秀值{$item}秒，扣{$array['audit_time_desc_score']}分";
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return implode(';', $res);
    }

    /**
     * @param CommonKpiContext $context
     * @return false|string
     */
    protected function merge(CommonKpiContext $context)
    {
        $context_arr = $context->toArray();
        foreach ($context_arr as $key => $item) {
            if (in_array($key, ['id', 'total_score', 'arm', 'exam_item'])) {
                unset($context_arr[$key]);
            }
        }
        return json_encode($context_arr);
    }
}
