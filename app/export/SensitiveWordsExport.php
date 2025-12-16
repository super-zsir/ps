<?php

namespace Imee\Export;


use Imee\Service\Domain\Service\Audit\SensitiveWordsService;

class SensitiveWordsExport extends BaseExport
{
    public function getTitle()
    {
        return [
            'ID', '用户id', '接收者id', '触发的敏感词', '原文内容', '触发原因', '词语场景', '类型', '时间'
        ];
    }

    public function export($filePathName, $filterParams)
    {
        $this->setFileHeader($filePathName);

        $params = $filterParams;
        $params['page'] = 1;
        $params['limit'] = 10000;

        $service = new SensitiveWordsService();
        $result = $service->list($params);
        $newArr = [];
        if (isset($result['data']) && $result['data']) {
            foreach ($result['data'] as $item) {
                $newArr[] = [
                    'id'       => $item['id'],
                    'uid'      => $item['uid'],
                    'to_uid'   => $item['to_uid'],
                    'word'     => $item['word'],
                    'content'  => $item['content'],
                    'reason'   => $item['reason'],
                    'cond'     => $item['cond'],
                    'type'     => $item['type'],
                    'dateline' => $item['dateline'],
                ];
            }
        }
        $tmpStr = $this->formatCsvTextBatch($newArr);
        file_put_contents($filePathName, $tmpStr, FILE_APPEND);
    }
}