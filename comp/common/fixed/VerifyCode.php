<?php
/**
 * 图形验证码
 */

namespace Imee\Comp\Common\Fixed;

use Phalcon\Di;

class VerifyCode
{
    const TYPE_NUMBER = 0;
    const TYPE_WORD = 1;
    const TYPE_WORD_NUMBER = 2;

    private $_type;
    private $_len;

    private $_code;
    private $_ip;
    private $_did;
    private $redis;

    public function __construct($type = self::TYPE_WORD, $len = 4)
    {
        $this->_type = $type;
        $this->_len = $len;
        $this->redis = Di::getDefault()->getShared('redis');
    }

    public function setIp($ip)
    {
        $this->_ip = $ip;
    }

    public function setDid($did)
    {
        $this->_did = $did;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function output($code = '')
    {
        //ip 限制
        if (!empty($this->_ip)) {
            $now = time();
            $ipLimitKey = 'smsimage.ip.limit.' . $this->_ip;
            $this->redis->zAdd($ipLimitKey, $now, $now);
            $this->redis->expire($ipLimitKey, 7200);
            $ipTotal = intval($this->redis->zcount($ipLimitKey, $now - 3600, $now));
            if ($ipTotal >= 9) {
                $code = 'FAIL';
            }
            $ipTotalKey = 'smsimage.ip.total.' . $this->_ip . ':' . date('Ymd');
            $dayIpTotal = $this->redis->get($ipTotalKey);
            if ($dayIpTotal >= 30) {
                $code = 'FAIL';
            }
            $this->redis->set($ipTotalKey, $dayIpTotal + 1, 86400);
        }

        if (!empty($this->_did)) {
            $day_sendsms_limit = 15;
            $sendsmslimitKey = 'smsimage.did.limit.' . $this->_did . ':' . date('Ymd');
            $sendsmslimits = $this->redis->get($sendsmslimitKey);
            if ($sendsmslimits >= $day_sendsms_limit) {
                $code = 'FAIL';
            }
            $this->redis->set($sendsmslimitKey, $sendsmslimits + 1, 86400);
        }

        if ($code) {
            $this->_code = $code;
        } else {
            $this->_code = $this->random();
        }
        $rndcodelen = strlen($this->_code);

        //创建图片，并设置背景色
        if (function_exists('imagecreate')) {
            $im = imagecreate(50, 20);
        } else {
            throw new \Exception("unsupport gd");
        }
        imageColorAllocate($im, 255, 255, 255);

        //背景线
        $lineColor1 = imageColorAllocate($im, 240, 220, 180);
        $lineColor2 = imageColorAllocate($im, 250, 250, 170);
        for ($j = 3; $j <= 16; $j = $j + 3) {
            imageline($im, 2, $j, 48, $j, $lineColor1);
        }
        for ($j = 2; $j < 52; $j = $j + (mt_rand(3, 6))) {
            imageline($im, $j, 2, $j - 6, 18, $lineColor2);
        }

        //画边框
        $bordercolor = imageColorAllocate($im, 0x99, 0x99, 0x99);
        imagerectangle($im, 0, 0, 49, 19, $bordercolor);

        //输出文字
        $fontColor = ImageColorAllocate($im, 48, 61, 50);
        for ($i = 0; $i < $rndcodelen; $i++) {
            $bc = mt_rand(0, 1);
            imagestring($im, 5, $i * 10 + 6, mt_rand(2, 4), $this->_code[$i], $fontColor);
        }

        ob_start();
        imagepng($im);
        imageDestroy($im);
        return ob_get_clean();
    }

    private function random()
    {
        $chars = '0123456789ABCDEFGHIJ0123456789KLMNOPQRSTJ0123456789UVWXYZ0123456789abcdefghijJ0123456789klmnopqrstJ0123456789uvwxyz0123456789';
        switch ($this->_type) {
            case self::TYPE_NUMBER:
                $chars = '0123456789';
                break;

            case self::TYPE_WORD:
                $chars = 'ABCDEFGHIJKLMNOPQRSTJUVWXYZabcdefghijJklmnopqrstJuvwxyz';
                break;
        }

        $max = strlen($chars) - 1;
        $hash = '';
        mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $this->_len; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return strtoupper($hash);
    }
}