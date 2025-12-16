<?php

namespace Imee\Service\Domain\Service\Csms\Process\Kanban;

use Imee\Models\Xss\CsmsKanbanMachine;
use Imee\Service\Domain\Context\PageContext;

class AutomationListProcess
{
    public function __construct(PageContext $context)
    {
        $this->context = $context;
    }



    public function handle()
    {
        $conditions = $bind = [];

        if ($this->context->format) {
            $format = $this->context->format;
        } else {
            $format = 'dateline';
        }

        if ($this->context->type) {
            $conditions[] =  "type = :type:";
            $bind['type'] = $this->context->type;
        }

        if ($this->context->beginTime) {
            $beginTime = strtotime($this->context->beginTime);
        } else {
            $beginTime = strtotime(date('Y-m-d', strtotime('-7 day')));
        }
        $conditions[] = "dateline >= :beginTime:";
        $bind['beginTime'] = $beginTime;

        if ($this->context->endTime) {
            $endTime = strtotime($this->context->endTime) + 24*60*60;
        } else {
            $endTime = strtotime(date('Y-m-d', strtotime('+1 day')));
        }
        $conditions[] = "dateline < :endTime:";
        $bind['endTime'] = $endTime;

        $columns = "dateline, week, type, audit, sum(total) as total, sum(machine_total) as machine_total, sum(machine_pass_num) as machine_pass_num,
        sum(machine_refuse_num) as machine_refuse_num, sum(refuse_exact_num) as refuse_exact_num, sum(pass_exact_num) as pass_exact_num,
        sum(machine_valid_total) as machine_valid_total, sum(machine_exact_total) as machine_exact_total";


        $totalData = CsmsKanbanMachine::find([
            'conditions' => implode(" and ", $conditions),
            'bind' => $bind,
            'group' => $this->groupBy($format),
        ])->toArray();
        $total = count($totalData);


        $data = CsmsKanbanMachine::find([
            'columns' => $columns,
            'conditions' => implode(" and ", $conditions),
            'bind' => $bind,
            'group' => $this->groupBy($format),
            'offset' => $this->context->offset ?: 0,
            'limit' => $this->context->limit ?: 15,
            'sort' => 'dateline',
            'dir' => 'asc'
        ])->toArray();

        $format = explode('_', $format);
        $format_date = $format[0] ?? '/';
        $format_type = $format[1] ?? '';
        if ($total && $data) {
            foreach ($data as &$item) {
                $item['datetime'] = isset($item[$format_date]) ? ($format_date== 'week' ? date('md周', $item[$format_date]) : date('Y-m-d', $item[$format_date])) : '/';

                $item['format'] = isset($item[$format_date]) ? date('Y-m-d', $item[$format_date]) : '/';

                $item['type_name'] = empty($format_type) ? CsmsKanbanMachine::$type[$item['type']] : '全部';
                // 机审检测率
                $item['check_percent'] = $item['total'] ? sprintf('%.2f', $item['machine_total'] / $item['total']) * 100 .'%' : '0%';
                // 机审占比
                $item['valid_percent'] = $item['total'] ? sprintf('%.2f', $item['machine_valid_total'] / $item['total']) : '0';
                // 人审占比
                $item['person_percent'] = (1 - $item['valid_percent']) * 100 . '%';
                $item['valid_percent'] = $item['valid_percent'] * 100 . '%';
                // 机审准确率
                $item['exact_percent'] = $item['machine_total'] ? sprintf('%.2f', ($item['pass_exact_num'] + $item['refuse_exact_num']) / $item['machine_total']) * 100 . '%' : '0%';
                // 机审通过准确率
                $item['pass_exact_percent'] = $item['machine_pass_num'] ? sprintf('%.2f', $item['pass_exact_num'] / $item['machine_pass_num']) * 100 . '%' : '0%';
                // 机审拒绝准确率
                $item['refuse_exact_percent'] = $item['machine_refuse_num'] ? sprintf('%.2f', $item['refuse_exact_num'] / $item['machine_refuse_num']) * 100 . '%' : '0%';
                // 机审识别率
                $item['work_percent'] = $item['machine_total'] ? sprintf('%.2f', ($item['machine_pass_num'] + $item['machine_refuse_num']) / $item['machine_total']) * 100 . '%' : '0%';
                // 机审生效量

                // 机审生效率
                $item['machine_valid_percent'] = $item['machine_total'] ? sprintf('%.2f', $item['machine_valid_total'] / $item['machine_total']) * 100 . '%' : '0%';
            }
        }

        if ($this->context->page == 1 && $totalData) {
            $huiData = CsmsKanbanMachine::findFirst([
                'columns' => $columns,
                'conditions' => implode(" and ", $conditions),
                'bind' => $bind,
            ]);
            if ($huiData) {
                $huiData = $huiData->toArray();
                $huiData['datetime'] = '汇总';
                $huiData['type_name'] = '汇总';
                // 机审检测率
                $huiData['check_percent'] = $huiData['total'] ? sprintf('%.2f', $huiData['machine_total'] / $huiData['total']) * 100 .'%' : '0%';
                // 机审占比
                $huiData['valid_percent'] = $huiData['total'] ? sprintf('%.2f', $huiData['machine_valid_total'] / $huiData['total']) : '0';
                // 人审占比
                $huiData['person_percent'] = (1 - $huiData['valid_percent']) * 100 . '%';
                $huiData['valid_percent'] = $huiData['valid_percent'] * 100 . '%';
                // 机审准确率
                $huiData['exact_percent'] = $huiData['machine_total'] ? sprintf('%.2f', ($huiData['pass_exact_num'] + $huiData['refuse_exact_num']) / $huiData['machine_total']) * 100 . '%' : '0%';
                // 机审通过准确率
                $huiData['pass_exact_percent'] = $huiData['machine_pass_num'] ? sprintf('%.2f', $huiData['pass_exact_num'] / $huiData['machine_pass_num']) * 100 . '%' : '0%';
                // 机审拒绝准确率
                $huiData['refuse_exact_percent'] = $huiData['machine_refuse_num'] ? sprintf('%.2f', $huiData['refuse_exact_num'] / $huiData['machine_refuse_num']) * 100 . '%' : '0%';
                // 机审识别率
                $huiData['work_percent'] = $huiData['machine_total'] ? sprintf('%.2f', ($huiData['machine_pass_num'] + $huiData['machine_refuse_num']) / $huiData['machine_total']) * 100 . '%' : '0%';
                // 机审生效量

                // 机审生效率
                $huiData['machine_valid_percent'] = $huiData['machine_total'] ? sprintf('%.2f', $huiData['machine_valid_total'] / $huiData['machine_total']) * 100 . '%' : '0%';
                array_unshift($data, $huiData);
            }
        }

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    /**
     * @param string $item
     * @return string|void
     */
    private function groupBy(string $item)
    {
        switch ($item) {
            case 'dateline':
                return 'dateline, type';
            case 'week':
                return 'week, type';
            case 'dateline_summit':
                return 'dateline';
            case 'week_summit':
                return 'week';
            default:
                break;
        }
    }
}
