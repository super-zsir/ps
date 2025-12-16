<?php
/**
 * 公共方法
 */

namespace Imee\Comp\Common\Fixed;

class Utility
{
    public static function array2string($arr): string
    {
        return http_build_query($arr);
    }

    public static function microtimeFloat(): float
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function safeString($string): string
    {
        if (empty($string)) {
            return $string;
        }
        return htmlspecialchars(strip_tags($string));
    }

    public static function addslashes($string, $force = false)
    {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::addslashes($val, $force);
            }
            return $string;
        } else {
            if ($force == false) {
                return MAGIC ? $string : addslashes($string);
            } else {
                return addslashes($string);
            }
        }
    }

    public static function getTimeDuration($dateline): string
    {
        $now = time();
        $diff = $now - $dateline;
        return self::getTimeDurationYear($diff);
    }

    public static function getTimeDurationYear($duration): string
    {
        $oneYear = 86400 * 365;
        $year = intval($duration / $oneYear);
        return ($year > 0 ? $year . '年' : '') . self::getTimeDurationDay($duration - $year * $oneYear);
    }

    private static function getTimeDurationDay($duration): string
    {
        $day = intval($duration / 86400);
        return ($day > 0 ? $day . '天' : '') . self::getTimeDurationHour($duration - $day * 86400);
    }

    private static function getTimeDurationHour($duration): string
    {
        $h = intval($duration / 3600);
        return ($h > 0 ? $h . '小时' : '') . self::getTimeDurationMin($duration - $h * 3600);
    }

    private static function getTimeDurationMin($duration): string
    {
        $m = intval($duration / 60);
        return ($m > 0 ? $m . '分' : '') . self::getTimeDurationSecond($duration - $m * 60);
    }

    private static function getTimeDurationSecond($duration): string
    {
        return max(1, $duration) . '秒';
    }

    /**
     * 生成指定范围的日期
     *
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public static function genRangeDate($startDate, $endDate): array
    {
        if ($startDate == $endDate) {
            return [$startDate];
        }
        $array = [];
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        // 防止程序异常出现死循环，一年最多366天(闰年)所以这里给此固定值
        $limit = 366;
        $i = 1;
        while ($limit--) {
            $next = strtotime("+$i day", $start);
            $i++;
            if ($next < $end) {
                $array[] = date('Y-m-d', $next);
            } else {
                break;
            }
        }

        array_unshift($array, $startDate);
        $array[] = $endDate;
        return $array;
    }

    public static function fileFormat($str): string
    {
        // 取文件后缀名
        $str = strtolower(pathinfo($str, PATHINFO_EXTENSION));
        // 图片格式
        $image = array('webp', 'jpg', 'png', 'ico', 'bmp', 'gif', 'tif', 'pcx', 'tga', 'bmp', 'pxc', 'tiff', 'jpeg', 'exif', 'fpx', 'svg', 'psd', 'cdr', 'pcd', 'dxf', 'ufo', 'eps', 'ai', 'hdri');
        // 视频格式
        $video = array('mp4', 'avi', '3gp', 'rmvb', 'gif', 'wmv', 'mkv', 'mpg', 'vob', 'mov', 'flv', 'swf', 'mp3', 'ape', 'wma', 'aac', 'mmf', 'amr', 'm4a', 'm4r', 'ogg', 'wav', 'wavpack');
        // 压缩格式
        $zip = array('rar', 'zip', 'tar', 'cab', 'uue', 'jar', 'iso', 'z', '7-zip', 'ace', 'lzh', 'arj', 'gzip', 'bz2', 'tz');
        // 文档格式
        $text = array('exe', 'doc', 'ppt', 'xls', 'wps', 'txt', 'lrc', 'wfs', 'torrent', 'html', 'htm', 'java', 'js', 'css', 'less', 'php', 'pdf', 'pps', 'host', 'box', 'docx', 'word', 'perfect', 'dot', 'dsf', 'efe', 'ini', 'json', 'lnk', 'log', 'msi', 'ost', 'pcs', 'tmp', 'xlsb');
        // 匹配不同的结果
        switch ($str) {
            case in_array($str, $image):
                return 'image';
            case in_array($str, $video):
                return 'video';
            case in_array($str, $zip):
                return 'zip';
            case in_array($str, $text):
                return 'text';
            default:
                return 'undefined';
        }
    }

    /**
     * 格式化文件大小转换
     * @param $size
     * @return string
     */
    public static function formatBytes($size): string
    {
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $units[$i];
    }

    public static function random($length, $type = 0)
    {
        switch ($type) {
            case 1:
                $chars = '0122859345889678901235839456782590123456782690';
                break;

            case 2:
                $chars = 'abcdefghijklmnopqrstuvwxyz';
                break;

            default:
                $chars = '01234567890abcdefghijklmnopqrstuvwxyz';
                break;
        }

        $max = strlen($chars) - 1;
        $hash = '';
        mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     *  根据身份证号码计算年龄
     * @param string $idcard 身份证号码
     * @return int $age
     */
    public static function getAge($idcard)
    {
        if (empty($idcard) || !self::validationFilterIdCard($idcard)) {
            return null;
        }

        if ((strlen($idcard) == 15)) {
            $idcard = self::idcard15To18($idcard);
        }

        //获得出生年月日的时间戳
        $date = strtotime(substr($idcard, 6, 8));
        //获得今日的时间戳
        $today = strtotime('today');
        //得到两个日期相差的大体年数
        $diff = floor(($today - $date) / 86400 / 365);
        //strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        return strtotime(substr($idcard, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;
    }

    public static function validationFilterIdCard($idcard)
    {
        if (strlen($idcard) == 18) {
            return self::idcardChecksum18($idcard);
        } elseif ((strlen($idcard) == 15)) {
            $idcard = self::idcard15To18($idcard);
            return self::idcardChecksum18($idcard);
        } else {
            return false;
        }
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    public static function idcardVerifyNumber($idcardBase)
    {
        if (strlen($idcardBase) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verifyNumberList = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcardBase); $i++) {
            $checksum += substr($idcardBase, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        return $verifyNumberList[$mod];
    }

    // 将15位身份证升级到18位
    public static function idcard15To18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
            }
        }
        return $idcard . self::idcardVerifyNumber($idcard);
    }

    // 18位身份证校验码有效性检查
    public static function idcardChecksum18($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcardBase = substr($idcard, 0, 17);
        if (self::idcardVerifyNumber($idcardBase) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }

    public static function getDeviceType()
    {
        //全部变成小写字母
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = 'android';

        //分别进行判断
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad') || strpos($agent, 'ipod')) {
            $type = 'ios';
        }

        return $type;
    }

    public static function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    public static function rankConvert($num)
    {
        if ($num >= 10000) {
            $num = round($num / 10000, 1) . 'w';
        } else if ($num >= 1000) {
            $num = round($num / 1000, 1) . 'k';
        }
        return $num;
    }

    public static function getOs($ua)
    {
        $item = array('platform' => '', 'version' => '');

        if (empty($ua)) {
            return $item;
        }

        if (stripos($ua, 'Android') !== false) {
            preg_match("/(?<=Android )[\d\.]{1,}/", $ua, $version);
            $version = isset($version[0]) ? $version[0] : '';

            $item['platform'] = 'android';
            $item['version'] = $version;
            return $item;
        }

        if (stripos($ua, 'iPhone') !== false) {
            preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $ua, $version);
            $version = isset($version[0]) ? $version[0] : '';

            $item['platform'] = 'iphone';
            $item['version'] = $version;
            return $item;
        }

        if (stripos($ua, 'iPad') !== false) {
            $item['platform'] = 'iphone';
            $item['version'] = '';
        }

        if (stripos($ua, 'ios') !== false) {
            $item['platform'] = 'iphone';
            $item['version'] = '';
        }

        return $item;
    }
}