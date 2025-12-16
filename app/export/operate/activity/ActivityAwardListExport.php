<?php

namespace Imee\Export\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xsst\XsstTemplateAwardListOperate;
use Imee\Service\Operate\Activity\AwardListService;
use Imee\Service\Rpc\PsService;

class ActivityAwardListExport
{
    public static function getHeadings(): array
    {
        return [
            '统计周期',
            '榜单序号',
            '榜单类型',
            '榜单名称',
            '积分统计方式',
            '榜单排名',
            '发奖方式',
            '所获奖励对应名称',
            '门槛',
            '用户uid',
            '榜单积分数',
            '积分返钻比例(%)',
            '奖励金额',
            '金额单位',
            '修改记录',
            '修改原因'
        ];
    }

    public static function export($filePathName, $filterParams)
    {
        $data = $filterParams;

        $actId = $data['act_id'];
        $cycle = $data['cycle'];
        $listId = $data['list_id_str'];

        $file = $filePathName;
        $config = BbcTemplateConfig::findOne($actId);
        $rankButtonList = BbcRankButtonList::findOne($listId);
        $rankScoreConfig = BbcRankScoreConfig::findOneByWhere([['button_list_id', '=', $listId], ['act_id', '=', $actId]]);
        $rankButtonTag = BbcRankButtonTag::findOne($rankButtonList['button_tag_id']);
        // 非周星榜总榜时cycles默认为0
        if ((new AwardListService())->isResetCycleFilter($config['vision_type'], $rankButtonTag['tag_list_type'])) {
            $cycle = 0;
        }

        // 榜单相关资源汇总
        $resource = [$config, $rankButtonList, $rankScoreConfig, $rankButtonTag];

        $search = ['act_id' => $actId, 'cycle' => $cycle, 'list_id' => $listId];
        list($flg, $data) = (new PsService())->getDiamondList($search);

        // excel
        $headings = self::getHeadings();
        $sheetName = '第' . ($cycle) . '轮周期';
        $excelData = [];
        if ($flg) {
            $lists = (new AwardListService())->formatData($data, $resource);
            foreach ($lists as $rec) {
                $log = XsstTemplateAwardListOperate::getListByWhere([
                    ['act_id', '=', $actId],
                    ['award_id', '=', (int)$rec['id']]
                ], '*', 'id desc', 1);
                $log = empty($log) ? [] : $log[0];
                $excelData[] = array(
                    $rec['cycle_str'],
                    $rec['list_id_str'],
                    $rec['rank_tag'],
                    $rec['button_list_name'],
                    $rec['score_config_type'],
                    $rec['rank'],
                    $rec['award_id_enum'],
                    $rec['award_id_rank'],
                    $rec['award_id_score'],
                    $rec['object_id'],
                    $rec['score'],
                    $rec['diamond_proportion'] ?: '/',
                    $rec['num'],
                    $rec['award_id_type'],
                    '奖励金额：' . array_get($log, 'init_num', 0) . ' -> ' . array_get($log, 'num', 0),
                    array_get($log, 'reason', '')
                );
            }
        }

        $sheet = Excel::createSheet($excelData, $sheetName)->headings($headings);
        Excel::export([$sheet])->store($file);
    }
}