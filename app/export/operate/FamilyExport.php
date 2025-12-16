<?php

namespace Imee\Export\Operate;

use Dcat\EasyExcel\Excel;
use Imee\Service\Lesscode\ExportService;
use Imee\Service\Operate\FamilyService;

class FamilyExport
{
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

    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'], $filterParams['lang']);

        $service = new FamilyService();
        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service) {
                // 每次获取1000条数据导入
                $pageSize = 1000;

                // 只查询前10页数据
                if ($page > 10) {
                    return [];
                }

                $data = $service->getListAndTotal(['page' => $page, 'limit' => $pageSize] + $filterParams);

                // 当数据库查不到值时会停止执行闭包内的逻辑
                return $data['data'];
            })
            ->headings($headings)
            ->store($filePathName);
    }
}