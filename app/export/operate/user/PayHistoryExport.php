<?php

namespace Imee\Export\Operate\User;

use Dcat\EasyExcel\Excel;
use Imee\Service\Operate\User\UserListService;

class PayHistoryExport
{
    public static function export($filePathName, $filterParams): bool
    {

        $headings = [
            'id'             => 'ID',
            'money'          => '改变金额',
            'op'             => '操作',
            'dateline'       => '时间',
            'reason_display' => '原因',
            'extra'          => '扩展',
            'rid'            => '房间ID',
            'to'             => '对象',
        ];

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams) {

                // 每次获取1000条数据导入
                $pageSize = 10000;

                // 只查询前10页数据
                if ($page > 5) {
                    return [];
                }

                $service = new UserListService();

                list($data,) = $service->payHistory(['page' => $page, 'limit' => $pageSize] + $filterParams);
                foreach ($data as &$item) {
                    $item['money'] = number_format($item['money'] / 100, 2, '.', '');
                }

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data;
            })
            ->headings($headings)
            ->store($filePathName);
    }
}