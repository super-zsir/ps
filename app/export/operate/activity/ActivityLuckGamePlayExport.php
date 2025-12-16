<?php

namespace Imee\Export\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Helper;
use Imee\Service\Operate\Activity\ActivityLuckGamePlayService;

class ActivityLuckGamePlayExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['act_id']);

        $service = new ActivityLuckGamePlayService();

        $baseData = [
            'act_id'   => $filterParams['act_id'],
            'act_name' => $filterParams['act_name'],
            'time'     => Helper::now($filterParams['start_time']) . '~~' . Helper::now($filterParams['end_time']),
        ];

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $baseData) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }
                if (BbcTemplateConfig::isWheelLotteryNewVersion($filterParams['act_id'])) {
                    $data = $service->getLuckDataNewList(['page' => $page, 'limit' => $pageSize] + $filterParams);
                } else {
                    $data = $service->getLuckDataList(['page' => $page, 'limit' => $pageSize] + $filterParams);
                }

                foreach ($data as &$item) {
                    $item = array_merge($baseData, $item);
                }
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data;
            })
            ->headings($headings)
            ->store($filePathName);
    }

    /**
     * 获取表头
     */
    private static function getHeader(int $id): array
    {
        if (BbcTemplateConfig::isWheelLotteryNewVersion($id)) {
            return [
                'act_id'            => '活动ID',
                'act_name'          => '活动名称',
                'time'              => '活动时间',
                'uid'               => '用户uid',
                'user_name'         => '用户名称',
                'sex'               => '用户性别',
                'total_score'       => '总积分',
                'low_use_number'    => '低级玩法使用次数',
                'low_use_score'     => '低级使用积分',
                'middle_use_number' => '中级玩法使用次数',
                'middle_use_score'  => '中级使用积分',
                'high_use_number'   => '高级玩法使用次数',
                'high_use_score'    => '高级使用积分',
                'surplus_score'     => '剩余积分',
            ];
        }

        return [
            'act_id'         => '活动ID',
            'act_name'       => '活动名称',
            'time'           => '活动时间',
            'uid'            => '用户uid',
            'user_name'      => '用户名称',
            'sex'            => '用户性别',
            'total_score'    => '总积分',
            'low_use_number' => '使用次数',
            'surplus_score'  => '剩余积分',
            'low_use_score'  => '使用积分',
            'award1_num'     => '中奖品1次数',
            'award2_num'     => '中奖品2次数',
            'award3_num'     => '中奖品3次数',
            'award4_num'     => '中奖品4次数',
            'award5_num'     => '中奖品5次数',
            'award6_num'     => '中奖品6次数',
            'award7_num'     => '中奖品7次数',
            'award8_num'     => '中奖品8次数',
            'award9_num'     => '中奖品9次数',
            'award10_num'    => '中奖品10次数',
        ];

    }
}