<?php

namespace Imee\Service;

use Imee\Comp\Common\Log\LoggerProxy;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Models\Config\BbcSettlementChannel;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserSettings;
use OSS\OssUpload;
use Phalcon\Di;
use Imee\Models\Xs\BaseModel as XsBaseModel;

class Helper
{

    public static $excludeAreaCodes = [
        'cn'    => '中国',
        'tw'    => '台湾',
        'hk'    => '香港',
        'mo'    => '澳门',
    ];

    public static $transformAreaCodeMap = [
        'cn'    => 'other',
        'tw'    => 'other',
        'hk'    => 'other',
        'mo'    => 'other',
    ];


    const ENDEKEY = '00f23fb61B@x2G*2156b3bd7c6bdd2ee';
    const ENDEIV = '1234567891011124';
    private static $_ip2Region = null;

    public static function getSystemUid()
    {
        $session = Di::getDefault()->getShared('session');

        return $session->get('uid');
    }

    public static function getSystemUserInfo()
    {
        $session = Di::getDefault()->getShared('session');
        return $session->get('userinfo');
    }

    public static function getSystemUserName()
    {
        $userInfo = self::getSystemUserInfo();
        return $userInfo['user_name'] ?? '';
    }

    public static function getSystemUserLanguage(int $uid = 0): array
    {
        $uid = $uid ?: self::getSystemUid();
        $language = CmsUser::findOne($uid)['language'] ?? '';

        return $language ? explode(',', $language) : [];
    }

    public static function debugger()
    {
        return LoggerProxy::instance();
    }

    public static function ip($trustForwardedHeader = true)
    {
        if (isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^[\d\.]{7,15}$/', $_SERVER['HTTP_X_REAL_IP'], $match)) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = Di::getDefault()->getShared('request')->getClientAddress($trustForwardedHeader);
        }
        if (self::isIntranet($ip) && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //如果是内网ip，这使用代理ip
            $clientIp = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
            $realIp = array_shift($clientIp);
            if (preg_match('/^[\d\.]{7,15}$/', $realIp, $match)) {
                return $realIp;
            }
        }
        return $ip;
    }

    public static function isIntranet($ip)
    {
        $ipLong = ip2long($ip);
        if (!$ipLong) {
            return false;
        }

        if (($ipLong & 0xff000000) === 0x0a000000) { //10.0.0.0 - 10.255.255.255
            return true;
        } elseif (($ipLong & 0xfff00000) === 0xac100000) { //172.16.0.0 - 172.31.255.255
            return true;
        } elseif (($ipLong & 0xffff0000) === 0xc0a80000) { //192.168.0.0 - 192.168.255.255
            return true;
        } elseif (strpos($ip, '127.') == 0) {
            return true;
        }

        return false;
    }

    public static function now($time = 0)
    {
        return date('Y-m-d H:i:s', $time ? $time : time());
    }

    public static function objectFilter($model, $fields)
    {
        if (empty($model) || empty($fields)) {
            return array();
        }
        $array = array();
        foreach ($fields as $key) {
            $array[$key] = $model->$key;
        }
        return $array;
    }

    public static function fetch($sql, array $bind = null, $schema = 'cms')
    {
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchAll($sql, \Phalcon\Db::FETCH_ASSOC, $bind);
    }

    public static function fetchOne($sql, array $bind = null, $schema = 'cms')
    {
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchOne($sql, \Phalcon\Db::FETCH_ASSOC, $bind);
    }

    public static function fetchColumn($sql, $schema = 'cms')
    {
        $conn = Di::getDefault()->getShared($schema);
        return $conn->fetchColumn($sql);
    }

    public static function exec($sql, $schema = 'cms')
    {
        $conn = Di::getDefault()->getShared($schema);
        if ($conn->execute($sql)) {
            return $conn->affectedRows();
        }
        return 0;
    }

    // 根据appid获取appname
    public static function getAppName($appid, $onlyMap = false)
    {
        $appMap = array(
            '0'  => '汇总',
            '5'  => 'Partying',
            '9'  => 'Party Star',
            '10' => 'Alloo',
            '11' => 'Veeka'
        );
        if ($onlyMap) {
            return $appMap;
        }

        return $appMap[$appid] ?? '';
    }

    public static function getAllApp()
    {
        return array(
            APP_ID => APP_NAME,
        );
    }

    public static function getBigareaArr()
    {
        return XsBigarea::getBigAreaList();
    }

    public static function getLanguageArr()
    {
        return XsBigarea::getLanguageArr();
    }

    public static function getBigareaIntMap()
    {
        return array_flip(XsBigarea::getAllBigAreaCode());
    }

    public static function getLanguageName($language)
    {
        return XsBigarea::getLanguageName($language);
    }

    public static function getOtherDateWeekDur($date)
    {
        $w = date('w', $date);
        $t = strtotime(date("Y-m-d", $date));

        $start = 0;
        $end = 0;

        if ($w == 0) {
            $start = $t - (86400 * 4);
            $end = $t + (86400 * 3);
        } elseif ($w == 1) {
            $start = $t - (86400 * 5);
            $end = $t + (86400 * 2);
        } elseif ($w == 2) {
            $start = $t - (86400 * 6);
            $end = $t + (86400 * 1);
        } elseif ($w == 3) {
            $start = $t - (86400 * 7);
            $end = $t;
        } elseif ($w == 4) {
            $start = $t - (86400 * 1);
            $end = $t + (86400 * 6);
        } elseif ($w == 5) {
            $start = $t - (86400 * 2);
            $end = $t + (86400 * 5);
        } elseif ($w == 6) {
            $start = $t - (86400 * 3);
            $end = $t + (86400 * 4);
        }

        return array(
            "start" => $start,
            "end"   => $end
        );
    }

    /**
     * @desc 根据日期获取周时间戳范围
     * @param string $date 日期
     * @return array
     */
    public static function getDateWeekDur($date)
    {
        $w = date('w', $date);
        $t = strtotime(date("Y-m-d", $date));

        $start = 0;
        $end = 0;

        if ($w <= 6 && $w > 1) {
            $start = $t - (($w - 1) * 86400);
            $end = $t + ((7 - $w) * 86400);
        } elseif ($w == 1) {
            $start = $t;
            $end = $t + (86400 * 7);
        } elseif ($w == 0) {
            $start = $t - (86400 * 6);
            $end = $t;
        }
        return array(
            "start" => $start,
            "end"   => $end
        );
    }

    /**
     * @desc 根据日期获取月时间戳范围
     * @param string $date 日期
     * @return array
     */
    public static function getDateMonthDur($date)
    {
        $startd = date('Y-m-01', $date);
        $endd = date('Y-m-d', strtotime("$startd +1 month -1 day"));

        $start = strtotime($startd);
        $end = strtotime($endd);

        return array(
            "start" => $start,
            "end"   => $end
        );
    }

    public static function arrayFilter($arr, $key)
    {
        if (empty($arr)) {
            return [];
        }
        return array_values(array_filter(array_unique(array_column($arr, $key))));
    }

    /**
     * 数据脱敏
     * @param $string 需要脱敏值
     * @param int $start 开始
     * @param int $length 结束
     * @param string $re 脱敏替代符号
     * @return bool|string
     * 例子:
     * dataDesensitization('18811113683', 3, 4); //188****3683
     * dataDesensitization('乐杨俊', 0, -1); //**俊
     */
    public static function dataDesensitization($string, $start = 0, $length = 0, $re = '*')
    {
        if (empty($string)) {
            return '';
        }
        $strarr = array();
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {//循环把字符串变为数组
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin = $start >= 0 ? $start : ($strlen - abs($start));
        $end = $last = $strlen - 1;
        if ($length > 0) {
            $end = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }
        for ($i = $begin; $i <= $end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin > $end || $begin > $last || $end > $last) {
            return false;
        }
        return implode('', $strarr);
    }

    public static function getsec2Time($time)
    {
        if (is_numeric($time)) {
            $value = array(
                "years"   => 0, "days" => 0, "hours" => 0,
                "minutes" => 0, "seconds" => 0,
            );
            if ($time >= 31556926) {
                $value["years"] = floor($time / 31556926);
                $time = ($time % 31556926);
            }
            if ($time >= 86400) {
                $value["days"] = floor($time / 86400);
                $time = ($time % 86400);
            }
            if ($time >= 3600) {
                $value["hours"] = floor($time / 3600);
                $time = ($time % 3600);
            }
            if ($time >= 60) {
                $value["minutes"] = floor($time / 60);
                $time = ($time % 60);
            }
            $value["seconds"] = floor($time);

            $t = '';
            if ($value['years'] > 0) {
                $t .= $value["years"] . "y";
            }
            if ($value['days'] > 0) {
                $t .= $value["days"] . "d,";
            }
            if ($value['hours'] > 0) {
                $t .= $value["hours"] . "h,";
            }
            if ($value['minutes'] > 0) {
                $t .= $value["minutes"] . "m,";
            }
            $t .= $value["seconds"] . 's';
            return $t;
        } else {
            return (bool)false;
        }
    }

    /**
     * 格式化金额数字
     * @param float $number
     * @param int $point
     * @return string
     */
    public static function formatMoney($number, $point = 2)
    {
        return sprintf("%0.{$point}f", $number);
    }

    /**
     * 输出百分比数据
     * @param float $number
     * @param int $point
     * @return string
     */
    public static function percentData($number, $point = 2)
    {
        return sprintf("%0.{$point}f%%", 100 * $number);
    }

    public static function debugInfo($msg = '', $log_id = '')
    {
        if (!defined('DEBUG') || !DEBUG) {
            return;
        }
        $traces = debug_backtrace();
        $trace_str = '';
        if (!empty($traces[1])) {
            $trace_str = $traces[1]['class'] . $traces[1]['type'] . $traces[1]['function'];
        }
        unset($traces);
        self::debugger()->info('====[' . $log_id . '] ' . $trace_str . ' debugInfo====' . $msg);
    }

    public static function getModelTableName($modelName)
    {
        $arr = explode('\\', $modelName);
        $modelName = end($arr);

        return snake_case($modelName);
    }

    public static function formatIdString($string)
    {
        if (!is_string($string)) {
            return [];
        }

        $string = str_replace('，', ',', $string);
        $ids = explode(',', $string);

        return self::formatIds($ids);
    }

    public static function formatIds($ids)
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        $ids = array_unique($ids);
        $ids = array_values($ids);
        return $ids;
    }

    /**
     * 格式化低代码下拉配置
     * @param array $data
     * @return array
     */
    public static function getFormatConfig($data = [])
    {
        $format = [];
        if ($data) {
            foreach ($data as $value => $label) {
                $format[] = ['value' => (string)$value, 'label' => $label];
            }
        }
        return $format;
    }

    public static function console($data, $isShowMemory = false)
    {
        if (PHP_SAPI != 'cli') {
            return;
        }

        $uuid = Di::getDefault()->getShared('uuid');
        if (true === $isShowMemory) {
            echo '[' . date('Y-m-d H:i:s') . "][$uuid]" . '[' . self::getMemoryUse() . ']' . print_r($data, true) . PHP_EOL;
        } else {
            echo '[' . date('Y-m-d H:i:s') . "][$uuid]" . print_r($data, true) . PHP_EOL;
        }
    }

    public static function getMemoryUse()
    {
        $memory = memory_get_usage() / 1024 / 1024;
        return number_format($memory, 3) . 'M';
    }

    public static function calP90($data = array())
    {
        if (empty($data)) {
            return 0;
        }
        $n = count($data);
        if ($n == 1) {
            return array_pop($data);
        }
        sort($data);
        $b = ($n - 1) * 0.9;
        $i = intval($b);
        $j = $b - $i;
        return sprintf('%.2f', (1 - $j) * $data[$i] + $j * $data[$i + 1]);
    }

    /**
     * 计算p90平均值 ： 比p90值小，再计算平均
     * @param $p90
     * @param array $data
     * @return  float
     */
    public static function calP90Ave($p90, $data = array())
    {
        if (empty($data)) {
            return 0;
        }

        foreach ($data as $k => $v) {
            if ($v > $p90) {
                unset($data[$k]);
            }
        }
        $count = count($data);
        if (empty($count)) {
            return 0;
        }

        return sprintf('%.2f', array_sum($data) / $count);
    }

    /**
     * 组装oss访问url
     * @param $url
     * @param bool $isLocal 是否使用内网地址
     * @param string $bucket
     * @return mixed|string
     */
    public static function getHeadUrl($url, bool $isLocal = false, $bucket = '')
    {
        if (!$url) {
            return '';
        }
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }

        $url = ltrim($url, '/');
        if (ENV == 'prod') {
            if ($isLocal) {
                $online = OSS_IMAGE_URL_LOCAL;
            } else {
                $online = OSS_IMAGE_URL_WEB;
            }
            return $online . '/' . $url;
        }

        return OSS_IMAGE_URL_TEST . '/' . $url;
    }

    /**
     * 返回url path部分
     * @param string $url
     * @return string
     */
    public static function getUrlPath(string $url): string
    {
        if (empty($url) || !preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }

        $parseUrl = parse_url($url);
        return ltrim($parseUrl['path'], '/');
    }

    /**
     * 过滤url中host
     * @param $url
     * @return array|mixed|string|string[]
     */
    public static function filterDomain($url)
    {
        if (!$url) {
            return '';
        }

        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            $parts = parse_url($url);
            $path = $parts['path'] ?? '';
            return ltrim($path, '/');
        }

        return $url;
    }

    public static function getOnlineUrl($url)
    {
        if (!$url) {
            return '';
        }
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }
        $url = ltrim($url, '/');
        return OSS_IMAGE_URL_WEB . '/' . $url;
    }

    /**
     * task 里面的打日志
     * @param $msg
     */
    public static function tasklog($msg)
    {
        if (IS_CLI) {
            echo "[" . date('Y-m-d H:i:s') . "]" . $msg . "\n";
        } else {
            Helper::debugger()->warning($msg);
        }
    }

    public static function strlen($str)
    {
        return mb_strlen($str, 'utf-8');
    }

    /**
     * 以第一个参数为准后面相同参数值不覆盖前面
     * @param $url
     * @return array
     */
    public static function convertUrlFirstParams($url): array
    {
        $query = parse_url($url);
        $query = $query['query'] ?? [];
        $queryParts = explode('&', $query);
        $params = [];
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            if (!isset($params[$item[0]])) {
                $params[$item[0]] = $item[1];
            }
        }
        return $params;
    }

    //根据管理ID获取管理员名称
    public static function getAdminName($admin)
    {
        $tmpA = CmsUser::findOne(intval($admin));
        return $tmpA ? $tmpA['user_name'] : $admin;
    }

    // 验证后台用户大区权限
    public static function checkAdminBigArea($adminId, $bigArea): bool
    {
        if (is_numeric($bigArea)) {
            $bigArea = XsBigarea::findOne($bigArea);
            $bigArea = $bigArea['name'];
        }

        $admin = CmsUser::findOne($adminId);

        if (!in_array($bigArea, explode(',', $admin['bigarea']))) {
            return true;
        }

        return false;
    }

    /**
     * @param array $condition
     * @return array
     */
    public static function filterWhere(array $condition): array
    {
        return array_filter($condition, function ($item) {
            if (!isset($item[2])) {
                return false;
            }
            if ($item[2] === '' || $item[2] === ['']) {
                return false;
            }
            return true;
        });
    }

    public static function trimParams(array $params)
    {
        return array_map([__CLASS__, 'filterTrim'], $params);
    }

    private static function filterTrim($param)
    {
        if (is_scalar($param)) {
            return trim($param);
        } elseif (is_array($param)) {
            return array_map([__CLASS__, 'filterTrim'], $param);
        }
        return $param;
    }

    /**
     * @param $bucket
     * @param $temp_url string example: /logs/tmp_upload/128674240_62b48d96303cf2.67377452.mov
     * @return \OSS\Http\ResponseCore|string
     * @throws \OSS\Core\OssException
     */
    public static function getOssUrl($temp_url, $bucket = OssUpload::PS_POINT, $timeOut = 1800)
    {
        $upload = new OssUpload($bucket);
        $client = $upload->client();
        return $client->signUrl($bucket, $temp_url, $timeOut);
    }

    /**
     * download file
     * @param string $filename
     * @param string $downloadName
     * @param string $ext
     * @return void
     */
    public static function downLoadFile(string $filename, string $downloadName = '', string $ext = 'csv', bool $isUnLink = true)
    {
        if (!$downloadName) {
            $downloadName = ((int)microtime(true) * 1000) . '.' . $ext;
        } else {
            $downloadName .= '.' . $ext;
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
        $isUnLink && @unlink($filename);
        exit();
    }

    /**
     * 获取链接资源大小
     * @param $url
     * @return mixed
     */
    public static function getLinkSize($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_exec($ch);

        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        return $size;
    }

    public static function formatUid(string $ids): array
    {
        if (empty(trim($ids))) {
            return [];
        }
        $ids = str_replace('，', ',', $ids);
        $ids = explode(',', trim($ids, ','));

        return self::handleIds($ids);
    }

    public static function handleIds($ids)
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);
        $ids = array_unique($ids);
        return array_values($ids);
    }

    public static function uploadOss($file, $timeOut = 86400)
    {
        $fileInfo = explode('/', $file);
        $fileName = end($fileInfo);

        $bucket = ENV == 'dev' ? OssUpload::PS_DEV_IMAGE : OssUpload::APC_ADMIN_DATA;
        $client = new OssUpload($bucket, ENV == 'dev' ? OssUpload::ENDPOINT_AP_OUT : OssUpload::ENDPOINT_OUT);
        $object = "logs/tmp_upload_ps/{$fileName}";
        if ($client->doesObjectExist($object)) {
            $client->delete($object);
        }

        $client->moveFileTo($file, $object);
        $res = $client->signUrl($object, $timeOut);
        return str_replace('-internal', '', $res);
    }

    /**
     * 验证文本是否包含emoji
     * @param string $text
     * @return bool
     */
    public static function hasEmoji(string $text): bool
    {
        $emojiPattern = '/[\x{1F000}-\x{1F6FF}|\x{1F900}-\x{1F9FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F1E0}-\x{1F1FF}|\x{2B50}|\x{2705}|\x{2B1B}|\x{2B1C}|\x{1F004}|\x{1F0CF}|\x{1F18E}]/u';
        return preg_match($emojiPattern, $text) === 1;
    }

    public static function encryptData($string)
    {
        return openssl_encrypt($string, 'AES-256-CBC', self::ENDEKEY, 0, self::ENDEIV);
    }

    public static function decryptData($string)
    {
        return openssl_decrypt($string, 'AES-256-CBC', self::ENDEKEY, 0, self::ENDEIV);
    }

    public static function getAdminBigArea($adminId): array
    {
        if (empty($adminId)) {
            return [];
        }
        $admin = CmsUser::findOne($adminId);
        $areaArr = $admin['bigarea'] ? explode(',', $admin['bigarea']) : [];
        return empty($areaArr) ? $areaArr : XsBigarea::getBigAreaIdByName($areaArr);
    }

    public static function getAdminLanguage($adminId): array
    {
        if( empty($adminId)){
            return [];
        };
        $admin = CmsUser::findOne($adminId);
        return $admin['language'] ? explode(',', $admin['language']) : [];
    }

    public static function isDate(&$date)
    {
        if (strlen($date) > 10) {
            $date = substr($date, 0, 10);
        }
        return $date && preg_match("/^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$/", $date);
    }

    public static function formatBirthday($birthday)
    {
        if ($birthday <= 0) {
            return '-';
        }

        if (preg_match('/^\d{8}$/i', $birthday)) {
            $result  = date('Y-m-d',strtotime($birthday));
        } else {
            $result = date('Y-m-d', $birthday);
        }

        return $result;

    }

    public static function formatDuration($seconds)
    {
        $units = [
            '年'    => 365 * 86400,
            '天'    => 86400,
            '小时'  => 3600,
            '分钟'  => 60,
            '秒'    => 1,
        ];

        $parts = [];
        foreach ($units as $name => $divisor) {
            $quot = floor($seconds / $divisor);
            if ($quot > 0) {
                $parts[] = "$quot $name";
                $seconds %= $divisor;
            }
        }

        return $parts ? implode(' ', $parts) : '0 秒';
    }

    public static function formatDurationExtended(int $seconds): string
    {
        $units = [
            '年'    => 365 * 86400,
            '月'    => 30 * 86400,
            '星期'  => 7 * 86400,
            '天'    => 86400,
            '小时'  => 3600,
            '分钟'  => 60,
            '秒'    => 1,
        ];

        $parts = [];
        foreach ($units as $name => $unitSeconds) {
            $count = floor($seconds / $unitSeconds);
            if ($count > 0) {
                $parts[] = "$count $name";
                $seconds %= $unitSeconds;
            }
        }

        return $parts ? implode(' ', $parts) : '0 秒';
    }

    public static function transformAreaToArray($country, $city = ''): array
    {
        $countryCode = array_search($country, self::$excludeAreaCodes);

        if (!empty($countryCode)) {
            return ['帕劳', ''];
        }

        return [$country, $city];
    }
    public static function translate($uid, $text, $param = [])
    {
        $uarea = XsUserSettings::findFirst($uid);
        $lang = $uarea->language ?? 'en';
        return __T($text, $param, strtolower($lang));
    }

    //必须在事务内调用加锁
    public static function getLock($keyLock, $schema = XsBaseModel::SCHEMA)
    {
        return Helper::fetchColumn("select get_lock('{$keyLock}', 2)", $schema);
    }

    //事务回滚和正常执行后都需要释放解锁
    public static function releaseLock($keyLock, $schema = XsBaseModel::SCHEMA)
    {
        return Helper::fetchColumn("select release_lock('{$keyLock}')", $schema);
    }

    /**
     * 根据用户ID获取新运营大区名
     * @param $uid
     */
    public static function getUserBigAreaNew($uid)
    {
        if (empty($uid)) return '-';
        $userArea = XsUserBigarea::findFirst($uid);
        if (!$userArea) return '-';
        $bigArea = XsBigarea::findFirst($userArea->bigarea_id);
        if (!$bigArea) return '-';
        return XsBigarea::$_bigAreaMap[$bigArea->name];
    }

    /**
     * 校验颜色是否填写正确
     * @param $str
     * @return bool
     */
    public static function checkColor($str): bool
    {
        $rule = "/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/";
        preg_match($rule, $str, $m);
        return (bool)($m[0] ?? false);
    }

    public static function exportSql($fileName, $tables = [], $createTable = false): string
    {
        $file = EXPORT_DIR . DS . $fileName;

        file_exists($file) && @unlink($file);

        $databaseConfigs = Di::getDefault()->getShared('config')->database;

        if ($createTable) {
            @file_put_contents($file, "SET sql_mode = 'NO_ZERO_DATE,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';" . PHP_EOL, FILE_APPEND);
            @file_put_contents($file, "SET NAMES utf8mb4;" . PHP_EOL, FILE_APPEND);
            @file_put_contents($file, "SET FOREIGN_KEY_CHECKS = 0;" . PHP_EOL, FILE_APPEND);
        }

        foreach ($tables as $info) {
            $table = $info['table'];
            $id = $info['id'];
            $schema = $info['schema'];
            $where = $info['where'];

            $dbName = isset($databaseConfigs[$schema]['dbname']) ? $databaseConfigs[$schema]['dbname'] : '';
            if (empty($dbName)) {
                continue;
            }
            $dbName = '`' . $dbName . '`';
            $table = '`' . $table . '`';

            $offset = 0;
            $limit = 1000;
            if ($createTable || empty($where)) {
                $ress = Helper::fetchOne("SHOW CREATE TABLE {$table}", null, $schema);
                if (!empty($ress)) {
                    $createSql = sprintf("DROP TABLE IF EXISTS %s.%s;", $dbName, $table) . PHP_EOL;
                    $createSql .= str_replace('CREATE TABLE ', 'CREATE TABLE ' . $dbName . '.', $ress['Create Table']) . ';' . PHP_EOL;
                    @file_put_contents($file, $createSql, FILE_APPEND);
                }
            }
            while (true) {
                if ($where) {
                    $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$id} ASC LIMIT {$offset}, {$limit}";
                } else {
                    $sql = "SELECT * FROM {$table} ORDER BY {$id} ASC LIMIT {$offset}, {$limit}";
                }

                $data = Helper::fetch($sql, null, $schema);
                if (empty($data)) break;
                $fields = implode(',', array_keys($data[0]));
                $sql = "REPLACE INTO {$dbName}.{$table}({$fields}) VALUES ";
                foreach ($data as $commodity) {
                    $value = '(';
                    foreach ($commodity as $v) {
                        if (strstr($v, "'")) {
                            $v = str_replace("'", "\'", $v);
                        }
                        $value .= "'{$v}',";
                    }
                    $sql .= rtrim($value, ',');
                    $sql .= '),';
                }
                $sql = rtrim($sql, ',');
                $sql = $sql . ';' . PHP_EOL;

                @file_put_contents($file, $sql, FILE_APPEND);
                @file_put_contents($file, PHP_EOL, FILE_APPEND);
                $offset += $limit;
            }
        }
        return $file;
    }

    public static function getSettlementChannelArr(): array
    {
        $data = BbcSettlementChannel::findAll();
        return $data ? array_column($data, 'name', 'type') : [];
    }

    public static function isMobile($phone)
    {
        return preg_match("/^(13|14|15|16|17|18|19)\d{9}$/", $phone) || preg_match("/^(\d{1,4})-(\d{6,13})$/", $phone);
    }

    public static function hasEmojis($string): bool
    {
        mb_internal_encoding('UTF-8');
        $regexp = '/(?:' . json_decode(file_get_contents(ROOT . DS . 'public/static/emoji/regexp.json')) . ')/u';
        if (preg_match_all($regexp, $string, $matches, PREG_OFFSET_CAPTURE)) {
            return true;
        }
        return false;
    }

    // 多语言支持翻译语种
    public static function getLanguageArray(): array
    {
        return ['en', 'ar'];
    }
}
