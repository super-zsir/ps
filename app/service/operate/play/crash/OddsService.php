<?php

namespace Imee\Service\Operate\Play\Crash;

use Imee\Exception\ApiException;
use Imee\Helper\Traits\ExportCsvTrait;
use Imee\Helper\Traits\ImportTrait;
use Imee\Models\Xs\XsRocketCrashTimeOdds;
use Imee\Models\Xsst\XsstRocketCrashOddsType;
use Imee\Service\Rpc\PsService;

class OddsService
{
    use ExportCsvTrait, ImportTrait;

    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getTableList(array $params): array
    {
        $list = XsstRocketCrashOddsType::getListAndTotal([], '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);
        if (empty($list['data'])) {
            return $list;
        }

        foreach ($list['data'] as &$item) {
            $item['type'] = $item['tid'];
            $item['odds'] = [
                'title' => '预览',
                'value' => '预览',
                'type' => 'manMadeModal',
                'modal_id' => 'table_modal',
                'params' => [
                    'guid' => 'crashoddsinfo',
                    'table_id' => $item['tid']
                ]
            ];
        }

       return $list;
    }

    public function getList(array $params, bool $isFormat = true): array
    {
        $page = intval($params['page'] ?? 1);
        $limit = intval($params['limit'] ?? 1000);
        $tableId = intval($params['table_id'] ?? 0);

        $filterParams = [
            'page_num' => $page,
            'page_size' => $limit,
            'table_id' => $tableId,
        ];

        list($res, $msg, $list) = $this->rpcService->getRocketCrashTimeOdds($filterParams);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        if ($isFormat) {
            foreach ($list['data'] as &$item) {
                $item['timeline'] = sprintf('%.1f', $item['timeline'] / 1000);
                $item['odds'] = sprintf('%.2f', $item['odds'] / 100);
                $item['rate'] = sprintf('%.6f', $item['rate'] / 1e6);
            }
        }

        return $list;
    }

    public function import(array $params, bool $isCreate = false): array
    {
        $beforeJson = $this->getList(['table_id' => $params['tid']], false)['data'] ?? [];
        $data = $this->formatData($params);
        if ($isCreate) {
            $this->addCrashType($params['tid']);
            [$res, $msg] = $this->rpcService->addRocketCrashTimeOdds($data);
        } else {
            [$res, $msg] = $this->rpcService->editRocketCrashTimeOdds($data);
        }
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $params['tid'], 'before_json' => $beforeJson, 'after_json' => $data];
    }

    public function export(array $params): string
    {
        $file = '/tmp/crashOddsInfo' . $params['admin_uid'] . time() . '.csv';
        $list = $this->getList(['table_id' => $params['tid']])['data'] ?? [];
        $tmp = [];
        foreach ($list as $item) {
            $tmp[] = [$item['timeline'], $item['odds'], $item['rate']];
        }

        $tmpStr = $this->formatCsvTextBatch($tmp);
        file_put_contents($file, $tmpStr, FILE_APPEND);

        return $file;
    }

    private function addCrashType(int $tid): void
    {
        $time = time();

        [$res, $_] = XsstRocketCrashOddsType::addOrEdit($tid, [
            'tid' => $tid,
            'create_time' => $time,
            'update_time' => $time,
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, '类型添加失败，请重新添加');
        }
    }

    private function formatData(array $params): array
    {
        $data = [];
        foreach ($params['data'] as $item) {
            $data[] = [
                'table_id' => (int)$params['tid'],
                'timeline' => $this->scaleUpAndCeil((string) $item['timeline'], 1000),
                'odds'     => $this->scaleUpAndCeil((string) $item['odds'], 100),
                'rate'     => $this->scaleUpAndCeil((string) $item['rate'], 1e6),
            ];
        }

        return $data;
    }

    /**
     * 高精度放大数值并向上取整
     * @param string $value 原始数值（转为字符串避免精度丢失）
     * @param int $scale 放大倍数（如 1000 表示 ×1000）
     * @return int 处理后的整数
     */
    private function scaleUpAndCeil(string $value, int $scale): int
    {
        // 先将数值乘以放大倍数（高精度乘法）
        $multiplied = bcmul($value, (string)$scale, 10); // 保留 10 位小数避免截断
        
        return (int)ceil((float)$multiplied);
    }

    public function uploadOdds(): array
    {
        [$res, $msg, $data] = $this->uploadCsv(['timeline', 'odds', 'rate']);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $data['data'];
    }
}