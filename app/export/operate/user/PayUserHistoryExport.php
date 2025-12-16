<?php

namespace Imee\Export\Operate\User;

use Dcat\EasyExcel\Excel;
use Imee\Service\Operate\User\UserListService;

class PayUserHistoryExport
{
    public static function export($filePathName, $filterParams): bool
    {

        $headings = [
            'id'                => 'ID',
            'money'             => '改变金额',
            'op'                => '操作',
            'dateline'          => '时间',
            'reason_display'    => '原因',
            'extra'             => '扩展',
            'rid'               => '房间ID',
            'to'                => '对象',
            'git_id'            => '礼物ID',
            'git_name'          => '礼物名称',
            'git_price'         => '礼物单价(钻石)',
            'git_num'           => '礼物数量',
            'git_discount'      => '礼物折扣(钻石)',
            'is_lucky'          => '是否幸运礼物',
            'lucky_divided'     => '幸运礼物配置比例',
            'property_id'       => '道具ID',
            'property_name'     => '道具名称',
            'property_price'    => '道具单价(钻石)',
            'property_num'      => '道具数量',
            'property_discount' => '道具折扣(钻石)',
        ];


        $service = new UserListService();
        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service) {
                // 每次获取1000条数据导入
                $pageSize = 10000;

                // 只查询前10页数据
                if ($page > 5) {
                    return [];
                }

                $data = $service->payUserHistory(['page' => $page, 'limit' => $pageSize] + $filterParams);

                foreach ($data['data'] as &$item) {
                    $item['money'] = number_format($item['money'] / 100, 2, '.', '');
                }

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }
}