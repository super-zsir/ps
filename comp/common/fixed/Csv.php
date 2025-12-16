<?php

namespace Imee\Comp\Common\Fixed;

class Csv
{
    /**
     * @param $header
     * @param $data
     * @param string $filename
     */
    public $baseDir = '/tmp/export';

    public function exportToCsv($header, $data, $filename = '')
    {
        $csv = $this->makeContent($header, $data);
        $filename = $filename ?: (int)(microtime(true) * 1000);
        header("Content-type: text/html; charset=utf-8");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . $filename . '.csv');
        header("Content-Transfer-Encoding:binary");
        echo $csv;
    }

    /**
     * @param $header
     * @param $data
     * @param string $filename
     * @return array
     */
    public function makeCsvFile($header, $data, string $filename = ''): array
    {
        $csv = $this->makeContent($header, $data);
        $filename = ($filename ?: (int)(microtime(true) * 1000));
        $date = date('Y-m-d');
        $parentFile = $this->baseDir . DS . $date;
        $filePath = $parentFile . DS . $filename;
        if (!is_dir($parentFile)) {
            if (!mkdir($parentFile, 0777, true) && !is_dir($parentFile)) {
                return ['code' => 1, 'msg' => sprintf('Directory "%s" was not created', $parentFile)];
            }
        }
        // 将一个字符串写入文件
        $re = file_put_contents($filePath . '.csv', $csv);
        if (!$re) {
            return ['code' => 1, 'msg' => '文件写入失败，文件路径：' . $filePath];
        }
        $url = get_base_url() . "download" . '/export?f=' . base64_encode($filename);
        return ['code' => 0, 'msg' => 'success', 'data' => ['url' => $url]];
    }

    /**
     * @param int $before_day 删除几天前的数据
     * @return bool
     */
    public function deleteCsvFile(int $before_day = 7): bool
    {
        if ($before_day < 0) {
            return false;
        }
        $critical_date = date('Y-m-d', strtotime('-' . $before_day . ' day'));
        $parentFile = $this->baseDir;
        if (!is_dir($parentFile)) {
            return true;
        }
        $dirs = scandir($parentFile);
        try {
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                //如果文件夹是日期 并且是 Y-m-d格式，以及该文件夹的名称对应的日期小于临界日期，则删除里面的内容
                if (date('Y-m-d', strtotime($dir)) === $dir && $dir <= $critical_date) {
                    array_map('unlink', glob($parentFile . DS . $dir . "/*.csv"));
                    rmdir($parentFile . DS . $dir);
                }
            }
        } catch (\Exception $e) {
            //
        }
        return true;
    }

    /**
     * 下载生成好的csv文件
     * @param string $filename 完整路径/tmp/demo.csv
     * @param string $downloadName
     * @return void
     */
    public function downLoadCsv(string $filename, string $downloadName = '')
    {
        if (!$downloadName) {
            $downloadName = ((int)microtime(true) * 1000) . '.csv';
        } else {
            $downloadName .= '.csv';
        }
        if (!file_exists($filename)) {
            return;
        }
        header("Content-type: text/html; charset=utf-8");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . $downloadName);
        header("Content-Transfer-Encoding:binary");
        echo file_get_contents($filename);
    }

    /**
     * @param $header
     * @param $data
     * @return string
     */
    private function makeContent($header, $data): string
    {
        $header = "\xEF\xBB\xBF" . implode(',', $header) . PHP_EOL;
        $content = '';
        foreach ($data as $_ => $val) {
            foreach ($val as &$va) {
                $va = '"' . str_replace(',', ';', $va) . '"';
            }
            $content .= implode(',', $val) . PHP_EOL;
        }
        return $header . $content;
    }

    public function exportToCsvLocal($header, $data, $filename = '')
    {
        // 处理默认文件名
        if (empty($filename)) {
            $filename = '/tmp/export_' . date('Ymd_His') . '.csv';
        }

        // 生成CSV内容
        $csvContent = $this->makeContent($header, $data);

        // 写入文件
        $bytesWritten = file_put_contents($filename, $csvContent);

        if ($bytesWritten === false) {
            throw new \RuntimeException("无法写入文件: $filename");
        }

        return $filename;
    }
}
