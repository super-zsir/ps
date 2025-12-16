<?php

namespace Imee\Export\Operate;

use Dcat\EasyExcel\Excel;
use Imee\Service\Operate\IssuedRoomStealthPrivilegeService;
use Imee\Service\Lesscode\ExportService;

class IssuedRoomStealthPrivilegeLogExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader($filterParams['guid'], $filterParams['lang']);

        $service = new IssuedRoomStealthPrivilegeService();

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service) {
                $pageSize = 1000;
                if ($page > 10) {
                    return [];
                }
                $params = array_merge($filterParams, ['page' => $page, 'limit' => $pageSize]);
                $result = $service->getLogList($params);
                $data = $result['data'] ?? [];
                if (empty($data)) {
                    return [];
                }
                // 可在此处对 $data 做格式化处理
                return $data;
            })
            ->headings($headings)
            ->store($filePathName);
    }

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