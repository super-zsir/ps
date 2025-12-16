<?php

namespace Imee\Export\Operate\Coupon;

use Dcat\EasyExcel\Excel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xsst\XsstCouponAreaLog;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\Coupon\CouponAreaManageService;

class CouponAreaLog
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'],$filterParams['lang']);

        $service = new CouponAreaManageService();
        $bigAreaList = XsBigarea::getBigAreaCnNameById();
        $type = XsstCouponAreaLog::$types;
        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $bigAreaList, $type) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getCouponAreaLogListAndTotal(['page' => $page, 'limit' => $pageSize] + $filterParams);
                foreach ($data['data'] as &$value) {
                    $value['bigarea_id'] = $bigAreaList[$value['bigarea_id']] ?? '';
                    $value['type'] = $type[$value['type']] ?? '';
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