<?php

namespace Imee\Helper\Traits;

use Dcat\EasyExcel\Excel;
use Phalcon\Di;

trait ImportTrait
{
    public function uploadCsv($columns): array
    {
        ini_set('memory_limit', '256M');
        
        $request = Di::getDefault()->get('request');
        try {
            if (!$request->hasFiles()) {
                throw new \Exception('没有上传文件');
            }

            $files = $request->getUploadedFiles();
            $file = $files[0];
            $filename = $file->getName();
            $fileTempName = $file->getTempName();
            // 限制文件大小，最大为10MB
            $maxFileSize = 10 * 1024 * 1024;
            if ($file->getSize() > $maxFileSize) {
                throw new \Exception('文件过大，最大允许10MB');
            }

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
            $lineCount = 0; // 行数计数器
            while (false !== ($data = fgetcsv($handle))) {
                $tmpRow = [];
                foreach ($csvVal as $k => $v) {
                    // 避免上传列有不是必填项
                    if (!isset($data[$k])) {
                        $data[$k] = '';
//                        return [false, '上传文件数据格式有误', ''];
                    }
                    //会导致data null时候需要过滤
                    if (is_null($data[$k])) {
                        break;
                    }
                    
                    $data[$k] = trim($data[$k], "\xEF\xBB\xBF"); //去掉bom头
                    // 更安全的编码检测和转换
                    if (!empty($data[$k])) {
                        $encode = mb_detect_encoding($data[$k], array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'), true);
                        if ($encode && $encode !== 'UTF-8') {
                            // 优先使用mb_convert_encoding，更安全
                            $converted = mb_convert_encoding($data[$k], 'UTF-8', $encode);
                            if ($converted !== false) {
                                $data[$k] = $converted;
                            } else {
                                // 备用方案：使用iconv但抑制错误
                                $converted = @iconv($encode, "UTF-8//IGNORE", $data[$k]);
                                if ($converted !== false) {
                                    $data[$k] = $converted;
                                }
                            }
                        }
                    }
                    $tmpRow[$v] = $data[$k];
                }

                $csvArr[] = $tmpRow;
                $lineCount++;
                if ($lineCount > 100000) {
                    return [false, '导入失败，文件数据超过10万行了，请检查文件是否有空行', ''];
                }
            }

            fclose($handle);

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

    /**
     * @param $columns
     * @param $sheetIndex
     * @return array
     * 参考：https://jqhph.github.io/easy-excel/docs/master/export.html
     */
    public function uploadEasyExcel($columns, $sheetIndex = 0): array
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

            $allowExtension = ['csv', 'ods', 'xlsx'];
            $ext = $file->getExtension();
            if (!in_array($ext, $allowExtension)) {
                throw new \Exception('上传格式不正确');
            }

            if ($fileTempName == '') {
                throw new \Exception('没有文件');
            }

            rename($fileTempName, $fileTempName . '.' . $ext);
            $allSheets = Excel::import($fileTempName . '.' . $ext)->toArray();

            $allSheets = array_values($allSheets);
            $allSheet = empty($allSheets[$sheetIndex]) ? [] : $allSheets[$sheetIndex];
            $csvArr = [];

            foreach ($allSheet as $item) {
                $_tmp = [];
                $index = 0;
                foreach ($columns as $k => $v) {
                    $_tmp[$k] = empty($item[$v]) ? (empty($item[$index]) ? '' : $item[$index]) : $item[$v];
                    $index++;
                }
                $csvArr[] = $_tmp;
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