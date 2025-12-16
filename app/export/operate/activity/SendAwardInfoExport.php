<?php

namespace Imee\Export\Operate\Activity;

use Dcat\EasyExcel\Excel;
use Imee\Comp\Common\Fixed\Utility;
use Imee\Models\Xs\XsActSendDiamondCheck;
use Imee\Models\Xs\XsBigarea;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\Activity\ActivityAccountManageService;

class SendAwardInfoExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);;

        $service = new ActivityAccountManageService();

        $bigAreaList = XsBigarea::getBigAreaCnNameById();
        $actTypeList = XsActSendDiamondCheck::$actTypeMap;

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $bigAreaList, $actTypeList) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getSendList(['page' => $page, 'limit' => $pageSize] + $filterParams);

                foreach ($data['data'] as &$value) {
                    $value['status'] = Utility::safeString($value['status']);
                    $value['bigarea_id'] = $bigAreaList[$value['bigarea_id']] ?? '';
                    $value['user_bigarea_id'] = $bigAreaList[$value['user_bigarea_id']] ?? '';
                    $value['act_type'] = $actTypeList[$value['act_type']] ?? '';
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