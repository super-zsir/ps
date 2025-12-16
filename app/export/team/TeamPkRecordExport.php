<?php

namespace Imee\Export\Team;

use Dcat\EasyExcel\Excel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsChatTeamPkDiamondRecord;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Rpc\PsService;

class TeamPkRecordExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);

        $service = new PsService();
        $bigAreaList = XsBigarea::getBigAreaCnNameById();
        $type = XsChatTeamPkDiamondRecord::$endType;

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $bigAreaList, $type) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                list($_, $_, $data) = $service->getTeamPkRecordList(['page' => $page, 'limit' => $pageSize] + $filterParams);

                if (empty($data['data'])) {
                    return [];
                }

                foreach ($data['data'] as &$value) {
                    $value['bigarea'] = $bigAreaList[$value['bigarea']] ?? '';
                    $value['end_type'] = $type[$value['end_type']] ?? '';
                    $value['red_rec_num'] = $value['red_rec_num']['title'];
                    $value['blue_rec_num'] = $value['blue_rec_num']['title'];
                    $value['total_send_num'] = $value['total_send_num']['title'];
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
    private static function getHeader($guid = '', $language = 'zh_cn'): array
    {
        static $header;
        if ($header) {
            return $header;
        }

        $header = ExportService::getListFields($guid, $language);
        return $header;
    }
}