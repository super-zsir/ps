<?php

namespace Imee\Export\Operate\Push;

use Dcat\EasyExcel\Excel;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\Push\PushManagementService;

class PushDetailExport
{
    /**
     * 获取表头
     */
    private static function getHeader(): array
    {
        return [
            'task_id'    => '任务Id',
            'uid'        => '发送对象Uid',
            'status'     => '发送状态',
            'dateline'   => '创建时间',
            'updateline' => '发送时间',
            'admin'      => '发送人',
        ];
    }

    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader();

        $service = new PushManagementService();
        $statusMap = [1 => '发送成功', 2 => '发送失败'];
        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $statusMap) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 100) {
                    return [];
                }
                
                $data = $service->getDetailListAndTotal(['page' => $page, 'limit' => $pageSize] + $filterParams);

                foreach ($data['data'] as &$item) {
                    $item['status'] = $statusMap[$item['status']] ?? '未发送';
                }

                $filterParams['max_id'] = end($data['data'])['id'];
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }
}