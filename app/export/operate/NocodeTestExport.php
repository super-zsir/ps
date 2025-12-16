<?php

namespace Imee\Export\Operate;

use Dcat\EasyExcel\Excel;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Operate\NocodeTestService;

class NocodeTestExport
{
    public static function export($filePathName, $filterParams): bool
    {
        $headings = self::getHeader();

        $service = new NocodeTestService();
        $userGender = XsUserProfile::$sex_arr;

        return Excel::export()
            ->chunk(function (int $page) use ($filterParams, $service, $userGender) {
                $pageSize = 1000;
                if ($page > 10) {
                    return [];
                }
                $params = array_merge($filterParams, ['page' => $page, 'limit' => $pageSize]);
                $result = $service->getListAndTotal($params);
                $data = $result['data'] ?? [];
                if (empty($data)) {
                    return [];
                }
                // 可在此处对 $data 做格式化处理
                foreach($data as &$item) {
                    $item['user_gender'] = $userGender[$item['user_gender']] ?? '';
                }

                return $data;
            })
            ->headings($headings)
            ->store($filePathName);
    }

    private static function getHeader(): array
    {
        return [
            'uid' => 'UID',
            'user_name' => 'user_name',
            'user_email' => 'user email',
            'user_gender' => 'user gender',
        ];
    }
}
