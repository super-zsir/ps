<?php

namespace Imee\Export\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayMultiwireService;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayService;

class ActivityTaskGamePlayMultiwireExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader();

        $service = new ActivityTaskGamePlayMultiwireService();

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 100) {
                    return [];
                }

                $data = $service->getExportList(['page' => $page, 'limit' => $pageSize] + $filterParams);
                if (empty($data['data'])) {
                    return [];
                }
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }

    /**
     * 获取表头
     */
    private static function getHeader(): array
    {
        return [
            'act_id'        => '活动ID',
            'act_name'      => '活动名称',
            'time'          => '活动时间',
            'tab_num'       => '任务Tab',
            'tag_list_type' => '任务重复周期',
            'cycle_time'    => '所处周期',
            'task_id'       => '任务项',
            'uid'           => '用户uid',
            'user_name'     => '用户名称',
            'sex'           => '用户性别',
            'bname'         => '用户公会名称',
            'bid'           => '用户公会id',
            'score'         => '分值'
        ];
    }
}