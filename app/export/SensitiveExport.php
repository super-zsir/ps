<?php

namespace Imee\Export;

use Imee\Service\Domain\Service\Audit\SensitiveService;

class SensitiveExport extends BaseExport
{
    public function getTitle()
    {
        return [
            'id', '敏感词', '平台', '类型', '原因', '场景', '时间', '拼音匹配', '精准匹配', '危险等级', '是否删除'
        ];
    }

    public function export($filePathName, $filterParams)
    {
        $this->setFileHeader($filePathName);
        $params = $filterParams;
        $params['page'] = 1;
        $params['limit'] = 10000;

        $service = new SensitiveService();
        $result = $service->getList($params);
        $newArr = [];
        if (isset($result['code']) && isset($result['data']) && $result['data']) {
            foreach ($result['data'] as $item) {
                $newArr[] = [
                    'id'            => $item['id'],
                    'text'          => $item['text'] ?? '',
                    'app_name'      => $item['app_name'],
                    'type_text'     => $item['type_text'],
                    'reason'        => $item['reason'],
                    'cond_text'     => $item['cond_text'],
                    'dateline'      => $item['dateline'],
                    'vague_text'    => $item['vague_text'],
                    'accurate_name' => $item['accurate_name'],
                    'danger'        => (isset($item['danger']) && $item['danger']) ? '高危' : '一般',
                    'deleted_text'  => $item['deleted_text']
                ];
            }
        }
        $tmpStr = $this->formatCsvTextBatch($newArr);
        file_put_contents($filePathName, $tmpStr, FILE_APPEND);
    }
}