<?php

namespace Imee\Export;

use Imee\Models\Xs\XsCommoditySend;
use Imee\Service\Commodity\CommoditySendService;
use Imee\Service\Lesscode\ExportService;

class CommoditySendExport extends BaseExport
{
    public function getTitle()
    {
        return array_values($this->getHeader());
    }

    public function export($filePathName, $filterParams)
    {
        $params = $filterParams;
        $keys = array_keys($this->getHeader($filterParams['guid'], $params['lang']));

        $this->setFileHeader($filePathName);
        $service = new CommoditySendService();
        $pageSize = 1000;
        $minId = 0;
        while (true) {
            $list = $service->getList($params, $minId, $pageSize);
            if (!$list) {
                break;
            }
            $minId = end($list)['id'];
            $newArr = [];
            foreach ($list as $value) {
                //转换
                $value['state'] = XsCommoditySend::$state[$value['state']] ?? '';
                //组装导出字段
                $item = [];
                foreach ($keys as $key) {
                    if (isset($value[$key])) {
                        $item[] = htmlspecialchars($value[$key]);
                    }
                }
                $newArr[] = $item;
            }
            $tmpStr = $this->formatCsvTextBatch($newArr);

            file_put_contents($filePathName, htmlspecialchars_decode($tmpStr), FILE_APPEND);
            if (count($list) < $pageSize) {
                break;
            }

            usleep(10 * 1000);
        }
    }

    /**
     * 获取表头
     */
    protected function getHeader($guid = '', $language = 'zh_cn'): array
    {
        static $header;
        if ($header) {
            return $header;
        }

        $header = ExportService::getListFields($guid, $language);
        return $header;
    }
}