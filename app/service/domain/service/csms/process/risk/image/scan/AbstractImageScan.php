<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Image\Scan;

use Imee\Comp\Common\Redis\RedisBase;
use Imee\Models\config\BbcTextWhiteList;
use Imee\Service\Domain\Service\Csms\Context\Risk\ImageProxyContext;

abstract class AbstractImageScan
{
    public static $allowQrcode = array(
        'https://weixin.qq.com/g/',
        'https://u.wechat.com/',
        'https://open.weixin.qq.com/connect/confirm',
        'https://www.iambanban.com',
        'https://pc.iambanban.com',
        'https://iambanban.com',
        'http://weixin.qq.com/r/3jqyqjLEXW8irXbJ92-M',
        'https://www.yinjietd.com',
        'http://www.xhjapp.com',
        'http://www.ddyinjie.com',
        'http://www.17tiaodong.com',
        'http://www.iyjtd.com',
        'http://www.51yinjie.com',
        'http://www.rewanplay.com',
        'http://www.imhotplay.com',
        'http://www.ttyinjie.com',
        'http://www.91hotplay.com',
        'http://www.17hotplay.com',
        'https://www.banban.chat',
        'http://www.iwsapp.com/',
        'http://www.zsmtqiye.com/',
        'https://www.zsmtqiye.com/',
        'https://work.weixin.qq.com/u/vc179d68d51d4b31bc',
        'https://work.weixin.qq.com/u/vc9088238f56b27894',
        'https://work.weixin.qq.com/u/vc11084f7361a24eae',
        'https://work.weixin.qq.com/u/vc0a395955eae7e5e7',
        'https://work.weixin.qq.com/u/vcd86d6c18de010588',
    );

    public static $disallowQrcode = array(
        'EImVXNl5GTJz6nwf8NnsbaI',
        'MMAtFUVpn_Zd2MYdXiZ5M7c',
        'MIpdwGvbLpyvRQKpavV3lCE'
    );

    /**
     * @var boolean 接口是否访问成功
     */
    protected $success = false;

    /**
     * @var string 违规原因
     */
    protected $reason = '';
    
    /**
     * @var boolean 是否命中严重违规
     */
    protected $seriousFlag = false;

    /**
     * @var boolean 设置是否严格模式
     */
    protected $strict;

    protected $data;

    public function __construct(ImageProxyContext $context)
    {
        $this->context = $context;
        $this->strict = $context->strict;
        $this->buildWhiteQrCode();

        $this->init();

    }



    abstract public function init();

    protected function buildWhiteQrCode()
    {
        //读取后台配置的二维码链接地址
        $redis = new RedisBase(RedisBase::REDIS_ADMIN);
        $qr_key = 'admin_allow_qr_code';
        $allowQrcode = $redis->get($qr_key);
        if ($allowQrcode) {
            $allowQrcode = unserialize($allowQrcode);
        } else {
            $allowQrcode = BbcTextWhiteList::find([
                'conditions' => 'type = :type:',
                'bind' => [
                    'type' => BbcTextWhiteList::TYPE_RQCODE
                ]
            ])->toArray();

            if (!empty($allowQrcode)) {
                $allowQrcode = array_column($allowQrcode, 'content');
            }
            $redis->set($qr_key, serialize($allowQrcode));
            $redis->expire($qr_key, 600);
        }

        if (!empty($allowQrcode)) {
            self::$allowQrcode = array_merge(self::$allowQrcode, $allowQrcode);
        }
    }
}
