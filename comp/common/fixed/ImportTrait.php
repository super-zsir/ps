<?php

namespace Imee\Comp\Common\Fixed;

use Phalcon\Di;

trait ImportTrait
{
    public function uploadCsv($columns, $maxRows = 100000): array
    {
        $request = Di::getDefault()->get('request');
        try {
            if (!$request->hasFiles()) {
                throw new \Exception('没有上传文件');
            }

            $files = $request->getUploadedFiles();
            $file = $files[0];
            $filename = $file->getName();
            $fileTempName = $file->getTempName();

            $allowExtension = ['csv'];
            $ext = $file->getExtension();
            if (!in_array($ext, $allowExtension)) {
                throw new \Exception('上传格式不正确');
            }

            if ($fileTempName == '') {
                throw new \Exception('没有文件');
            }

            $handle = fopen($fileTempName, 'r');
            if (false === $handle) {
                throw new \Exception('打开文件失败');
            }
            @setlocale(LC_ALL, 'zh_CN');
            $csvVal = $columns;
            $csvArr = [];
            $rowCounter = 0;

            while (false !== ($data = fgetcsv($handle))) {
                $rowCounter++;

                if ($rowCounter > $maxRows) {
                    throw new \Exception(sprintf('最多允许上传%d条数据', $maxRows));
                }
                $tmpRow = [];
                foreach ($csvVal as $k => $v) {
                    //会导致data null时候需要过滤
                    if (is_null($data[$k])) {
                        break;
                    }
                    if (!isset($data[$k])) {
                        return [false, '上传文件数据格式有误', ''];
                    }
                    $encode = mb_detect_encoding($data[$k], array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                    $pos = strpos($data[$k], "\xEF\xBB\xBF");

                    if ($pos === 0 && $encode != 'UTF-8') {
                        $data[$k] = trim($data[$k], "\xEF\xBB\xBF");
                    }
                    if ($encode != 'UTF-8') {
                        $data[$k] = iconv($encode, "UTF-8//IGNORE", $data[$k]);
                    }
                    $tmpRow[$v] = $data[$k];
                }

                $csvArr[] = $tmpRow;
            }

            //去掉行头
            if ($csvArr) {
                foreach ($columns as $v) {
                    if (in_array($v, array_values($csvArr[0]))) {
                        unset($csvArr[0]);
                        break;
                    }
                }
            }

            $res = [
                'name' => $filename,
                'data' => $csvArr,
            ];
        } catch (\Exception $e) {
            return [false, $e->getMessage(), ''];
        }

        return [true, '', $res];
    }
}
