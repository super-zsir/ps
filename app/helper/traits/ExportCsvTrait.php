<?php

namespace Imee\Helper\Traits;

use Exception;
use Imee\Comp\Common\Redis\RedisBase;
use Imee\Comp\Common\Redis\RedisHash;

trait ExportCsvTrait
{
    /**
     * @desc 根据hash和用户id获取需要生成的文件名
     * @param string $hashKey rediskey
     * @param string $adminUid 用户uid
     * @return string
     */
    protected function getRandFile(string $hashKey, string $adminUid): string
    {
        //生成随机文件名
        if (!is_dir(EXPORT_DIR)) {
            mkdir(EXPORT_DIR, 0777, true);
            chmod(EXPORT_DIR, 0777);
        }
        $redis = new RedisHash(RedisBase::REDIS_ADMIN);
        $nameArr = $redis->get('hash.' . $hashKey, $adminUid);
        if (is_array($nameArr)) {
            foreach ($nameArr as $k => $v) {
                if ($k == $adminUid) {
                    $file = EXPORT_DIR . DS . $v . '.csv';
                }
            }
        } else {
            $file = EXPORT_DIR . DS . $nameArr . '.csv';
        }
        //判断文件
        if (!empty($file)) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        return $file;
    }

    private function formatCsvTextLine($arrLine, $needIconv = false): string
    {
        $tmpSplit = 'split' . time() . 'split';
        $tmpStr = implode("{$tmpSplit}", $arrLine);
        //先转码再替换
        if ($needIconv) {
            $tmpStr = iconv("UTF-8", "gbk//IGNORE", $tmpStr);
        }
        //逗号替换
        $tmpStr = str_replace(",", " ", $tmpStr);
        //单双引号，会导致表格混乱，须替换
        $tmpStr = str_replace('"', " ", $tmpStr);
        $tmpStr = str_replace("'", " ", $tmpStr);
        //换行替换
        $tmpStr = str_replace("\r", " ", $tmpStr);
        $tmpStr = str_replace(PHP_EOL, " ", $tmpStr);
        $tmpStr = strip_tags($tmpStr);
        $tmpStr .= "\n";
        return str_replace("{$tmpSplit}", ",", $tmpStr);
    }

    public function formatCsvTextBatch($dataArr, $needIconv = false): string
    {
        $tmpStr = '';
        foreach ($dataArr as $v) {
            $lineStr = $this->formatCsvTextLine($v, $needIconv);
            $tmpStr .= $lineStr;
        }
        return $tmpStr;
    }

    /**
     * mysql 超时处理
     * @param $errorMessage
     * @return bool
     * @throws Exception
     */
    public function mysqlErrorException($errorMessage): bool
    {
        if (stripos($errorMessage, 'MySQL server has gone away') !== false
            || stripos($errorMessage, 'Lost connection to MySQL server during query') !== false
            || stripos($errorMessage, 'Can\'t connect to MySQL server') !== false
            || stripos($errorMessage, 'Trying to call method exec on a non-object') !== false
        ) {
            throw new Exception($errorMessage);
        }

        return true;
    }
}