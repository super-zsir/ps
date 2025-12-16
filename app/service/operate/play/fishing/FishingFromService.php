<?php

namespace Imee\Service\Operate\Play\Fishing;

use Imee\Exception\ApiException;
use Imee\Helper\Traits\ExportCsvTrait;
use Imee\Helper\Traits\ImportTrait;
use Imee\Service\Rpc\PsGameRpcService;

class FishingFromService
{
    use ImportTrait, ExportCsvTrait;
    /**
     * @var PsGameRpcService $rpcService
     */
    private $rpcService;
    public function __construct()
    {
        $this->rpcService = new PsGameRpcService();
    }

    public function getList(): array
    {
        // 只有一条数据，直接写死。具体数据在fishingfrominfo里面
        return [
            [
                'id' => 0,
                'fish_list' => [
                    'title' => '预览',
                    'value' => '预览',
                    'type' => 'manMadeModal',
                    'modal_id' => 'table_modal',
                    'params' => [
                        'guid' => 'fishingfrominfo',
                    ]
                ],
            ]
        ];
    }

    public function getInfoList(): array
    {
        list($res, $msg, $data) = $this->rpcService->getFishList();
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $data;
    }

    public function import(array $params): array
    {
        $data = array_map(function ($item) {
            return array_map('intval', $item);
        }, $params['data']);
        list($res, $msg) = $this->rpcService->upFishList($data);
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        return ['after_json' => $params['data']];
    }

    public function export(array $params): string
    {
        $file = '/tmp/fishingFromInfo' . $params['admin_uid'] . time() . '.csv';
        $data = $this->getInfoList();

        $tmpStr = $this->formatCsvTextBatch($data);
        file_put_contents($file, $tmpStr, FILE_APPEND);

        return $file;
    }

    public function uploadOdds(): array
    {
        [$res, $msg, $data] = $this->uploadCsv([
            "id", "fishCount", "in1", "out1", "in2", "out2",
            "in3", "out3", "in4", "out4", "in5", "out5"
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return $data['data'];
    }
}