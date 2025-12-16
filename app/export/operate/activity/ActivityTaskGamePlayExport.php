<?php

namespace Imee\Export\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Service\Operate\Activity\ActivityTaskGamePlayService;

class ActivityTaskGamePlayExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['rank_object'], $filterParams['type']);

        $service = new ActivityTaskGamePlayService();

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getTaskAndExchangeDataList(['page' => $page, 'limit' => $pageSize] + $filterParams);
                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data;
            })
            ->headings($headings)
            ->store($filePathName);
    }

    /**
     * 获取表头
     */
    private static function getHeader($rankObject, $type): array
    {
        if ($type == BbcTemplateConfig::TYPE_EXCHANGE) {
            return [
                'act_id'        => '活动ID',
                'act_name'      => '活动名称',
                'time'          => '活动时间',
                'uid'           => '用户uid',
                'user_name'     => '用户名称',
                'bname'         => '用户公会名称',
                'bid'           => '用户公会id',
                'score'         => '总积分',
                'use_score'     => '使用积分',
                'surplus_score' => '剩余积分',
                'reward_info'   => '商品明细'
            ];
        }
        $headers = [
            'act_id'        => '活动ID',
            'act_name'      => '活动名称',
            'time'          => '活动时间',
            'tag_list_type' => '任务重复周期',
            'cycle_time'    => '所处周期',
            'uid'           => '用户uid',
            'user_name'     => '用户名称',
            'sex'           => '用户性别',
            'bname'         => '用户公会名称',
            'bid'           => '用户公会id',
            'score'         => '分值'
        ];

        if ($rankObject == BbcRankButtonTag::RANK_OBJECT_CP) {
            $headers = [
                'act_id'        => '活动ID',
                'act_name'      => '活动名称',
                'time'          => '活动时间',
                'tag_list_type' => '任务重复周期',
                'cycle_time'    => '所处周期',
                'uid1'          => '用户1uid',
                'user1_name'    => '用户1名称',
                'sex1'          => '用户1性别',
                'bname1'        => '用户1公会名称',
                'bid1'          => '用户1公会id',
                'uid2'          => '用户2uid',
                'user2_name'    => '用户2名称',
                'sex2'          => '用户2性别',
                'bname2'        => '用户2公会名称',
                'bid2'          => '用户2公会id',
                'score'         => '分值'
            ];
        } else if ($rankObject == BbcRankButtonTag::RANK_OBJECT_BROKER) {
            $headers = [
                'act_id'        => '活动ID',
                'act_name'      => '活动名称',
                'time'          => '活动时间',
                'bid'           => '公会id',
                'bname'         => '公会名称',
                'uid'           => '公会长uid',
                'user_name'     => '公会长名称',
                'score'         => '公会积分',
            ];
        } else if ($rankObject == BbcRankButtonTag::RANK_OBJECT_ROOM) {
            $headers = [
                'act_id'        => '活动ID',
                'act_name'      => '活动名称',
                'time'          => '活动时间',
                'tag_list_type' => '任务重复周期',
                'cycle_time'    => '所处周期',
                'uid'           => '房主id',
                'user_name'     => '房主名称',
                'sex'           => '房主性别',
                'bname'         => '房主公会名称',
                'bid'           => '房主公会id',
                'score'         => '分值'
            ];
        }
        return $headers;
    }
}