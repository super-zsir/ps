<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Workbench;

use Imee\Comp\Common\Sdk\SdkIm;
use Imee\Models\Cms\CmsChatService;
use Imee\Service\Helper;

class ChatInitProcess
{
    const ENCODE = 0;
    const DECODE = 1;
    const AUTH_KEY = '^&(tre)%29^*';
    const CHAT_KEY = ENV == 'prod' ? '*/#0d%08p:' : 'test'; //websocket

    public function handle()
    {
        $uid = Helper::getSystemUid();

        $services = $language = array();
        $temp = CmsChatService::findByUserId($uid);
        foreach ($temp as $val) {
            $service = intval($val->service);
            $services[] = $service;
            if (!empty($val->language)) {
                $language[$service] = $val->language;
            }
        }
        $data = array(
            'uid'      => $uid,
            'services' => implode(',', $services),
            'language' => $language,
            'time'     => time(),
        );
        return array(
            'token'  => self::encode($data, self::CHAT_KEY, 86400),
            'appkey' => SdkIm::getAppkey()
        );
    }

    public static function array2string($arr)
    {
        return http_build_query($arr);
    }

    public static function encode($data, $key = self::AUTH_KEY, $expiry = 2592000)
    {
        if (is_array($data)) {
            $data = self::array2string($data);
        }
        return self::authcode($data, self::ENCODE, $key, $expiry);
    }

    public static function decode($string, $key = self::AUTH_KEY)
    {
        return self::authcode($string, self::DECODE, $key);
    }

    private static function authcode($string, $operation, $key, $expiry = 2592000)
    {
        if ($operation == self::DECODE) {
            $string = urldecode(str_replace("__", "%", $string));
        }
        $ckey_length = 4; //note 随机密钥长度 取值 0-32;
        // note 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // note 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // note 当此值为 0 时，则不产生随机密钥
        $key = md5($key);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == self::DECODE ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == self::DECODE ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == self::DECODE) {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return str_replace("%", "__", urlencode($keyc . str_replace('=', '', base64_encode($result))));
        }
    }
}
