<?php

namespace Imee\Models\Recharge;

use Imee\Comp\Common\Redis\RedisHash;

class XsIapConfig extends BaseModel
{
    // 商品币数的缓存键名
    const PRODUCT_EXCHANGE_CACHE = 'hash.oversea_product_exchange';

    // shareitpay信用卡通道
    const SHAREITPAY_CREDIT_CARD_CHANNEL = ['shareitpay.tur_card', 'shareitpay.egy_card', 'shareitpay.lbn_card', 'shareitpay.jor_card', 'shareitpay.bhr_card', 'shareitpay.omn_card', 'shareitpay.qat_card', 'shareitpay.kwt_card', 'shareitpay.are_card', 'shareitpay.sau_card'];

    const COMPANY_MAP = [
        'a' => 'ola party',
        'b' => 'ola chat',
        'c' => 'gumdrop',
    ];

    const STATE_MAP = [
        0 => '禁用',
        1 => '正常',
    ];

    const ROLE_MAP = [
        1 => '普通用户',
        2 => '代充',
        3 => '钻石特殊定价',
    ];

    public static function getChanelAndIdMap()
    {
        $iapconfigs = XsIapConfig::find(array(["app = '" . IAP_APPNAME . "'"]))->toArray();

        $payChannels = XsPayChannel::find()->toArray();
        $payChannels = array_column($payChannels, null, 'name');
        $chmap = array();
        $idmap = array();

        foreach ($iapconfigs as $v) {
            $channel = explode(".", $v["product_id"]);
            array_pop($channel);
            $channel = implode(".", $channel);

            if (in_array($v['platform'], array('coda', 'upay', 'razer', 'mentwall', 'mycard', 'shareitpay', 'adyen', 'fomo', 'vtc', 'yiyunh', 'stripe'))) {
                if (!isset($chmap[$channel])) $chmap[$channel] = array('platform' => $v['platform'], 'channel' => $channel, 'pids' => array());
                $chmap[$channel]['pids'][] = $v['id'];
            }
            $vattype = $payChannels[$v['channel']]['vat'] ?? 0;
            $rate = 10000 - $v['channel_rate'];
            $vattype == 1 && $rate -= $v['vat_rate'];
            $idmap[$v['id']] = array(
                'platform'   => $v['platform'],
                'channel'    => $channel,
                'money'      => $v['exchange'] / 100,
                'product_id' => $v["product_id"],
                'subject'    => $v["subject"],
                'rate'       => round($v['channel_rate'] / 100, 2),
                'ratemoney'  => $rate > 0 ? round($v['exchange'] * $v['channel_rate'] / $rate / 1000, 4) : 0,
                'vattype'    => $vattype,
                'vatrate'    => round($v['vat_rate'] / 100, 2),
                'vatmoney'   => $rate > 0 ? round($v['exchange'] * $v['vat_rate'] / $rate / 1000, 4) : 0,
                'exchange'   => $v['exchange']
            );
        }

        return array('chmap' => $chmap, 'idmap' => $idmap);
    }

    public static function getIapBatch($ids)
    {
        if (!$ids) return [];
        $iapconfigs = self::find(['id in ({id:array})', 'bind' => ['id' => $ids]])->toArray();
        $payChannels = XsPayChannel::find()->toArray();
        $payChannels = array_column($payChannels, 'vat', 'name');
        $result = [];
        foreach ($iapconfigs as $v) {
            $vattype = $payChannels[$v['channel']] ?? 0;
            $rate = 10000 - $v['channel_rate'];
            $vattype == 1 && $rate -= $v['vat_rate'];
            $result[$v['id']] = array(
                'platform'   => $v['platform'],
                'channel'    => $v['channel'],
                'product_id' => $v['product_id'],
                'subject'    => $v['subject'],
                'rate'       => round($v['channel_rate'] / 100, 2),
                'ratemoney'  => $rate > 0 ? round($v['exchange'] * $v['channel_rate'] / $rate / 1000, 4) : 0, // 通道成本/美元
                'vattype'    => $vattype,
                'vatrate'    => round($v['vat_rate'] / 100, 2),
                'vatmoney'   => $rate > 0 ? round($v['exchange'] * $v['vat_rate'] / $rate / 1000, 4) : 0, // vat成本/美元
                'exchange'   => $v['exchange']
            );
        }

        return $result;
    }

    public static function getChannel()
    {
        $rate_map = self::$_rateMap;
        return array_keys($rate_map);
    }

    private static $_rateMap = array(
        'com.imbb.oversea.vip'    => 30, // iap
        'com.peipei.oversea.coin' => 30, // iap
        'com.ola.chat.diamond'    => 30, // iap
        'com.ola.chat.bean'       => 30, // iap
        'com.ola.chat.coin'       => 30, // iap
        'com.ola.chat.v'          => 30, // iap
        'com.ola.chat.vip'        => 30, // iap
        'com.im.xhj.coin'         => 30, // giap
        'sg.partying.bean'        => 30, // giap
        'sg.partying.gdiamond'    => 30, // giap

        'fomo'          => 2, // fomo 微信
        'fomo.ali'      => 3.5,
        'fomo.sgp_card' => 5,

        'paypal' => 5,

        'p99'      => 22,
        'p99.tel'  => 22,
        'p99.card' => 12,

        'coda.sgp_starhub'      => 30,
        'coda.sgp_m1'           => 30,
        'coda.sgp_grabpay'      => 7,
        'coda.mys_celcom'       => 30,
        'coda.mys_umobile'      => 30,
        'coda.mys_maxis'        => 30,
        'coda.mys_digi'         => 30,
        // 'coda.mys_fpx' => 5,
        'coda.mys_grabpay'      => 7,
        'coda.mys_touchgo'      => 7,
        'coda.mys_boostewallet' => 7,
        'coda.idn_smartfren'    => 30,
        'coda.idn_telkomsel'    => 40,
        'coda.idn_indosat'      => 30,
        'coda.idn_xl'           => 30,
        'coda.idn_3'            => 35,
        // 'coda.idn_bank' => 5,
        'coda.idn_gopay'        => 10,
        // 'coda.idn_alfamart' => 5,
        'coda.idn_doku'         => 3.5,
        'coda.idn_ovo'          => 5,
        'coda.idn_dana'         => 5,
        'coda.idn_linkaja'      => 5,
        'coda.tha_rabbit'       => 10,
        'coda.tha_truemw'       => 11,
        'coda.tha_dtac'         => 30,
        'coda.tha_ais'          => 30,
        'coda.tha_cat'          => 30,
        'coda.tha_truemove'     => 30,
        'coda.tha_banktransfer' => 7,
        'coda.are_etisalat'     => 32,
        'coda.kwt_zain'         => 35,
        'coda.bhr_zain'         => 32,

        //		'upay.vnm_vnp' => 32,
        //		'upay.vnm_vtt' => 32,
        //		'upay.vnm_vms' => 32,
        'upay.vnm_momo'         => 5,
        'upay.vnm_zing'         => 25,
        'upay.vnm_fpt'          => 25,
        'upay.vnm_bank'         => 5,

        'razer.twn_wallet'    => 25,
        'razer.twn_pin'       => 25,
        'razer.hkg_pin'       => 20,
        'razer.hkg_wallet'    => 20,
        'razer.sgp_pin'       => 25,
        'razer.sgp_wallet'    => 25,
        'razer.sgp_singtel'   => 30,
        'razer.sgp_enets'     => 25,
        'razer.mys_fpx'       => 20,
        'razer.mys_wallet'    => 20,
        'razer.mys_pin'       => 20,
        'razer.idn_pin'       => 20,
        'razer.idn_wallet'    => 20,
        'razer.idn_wallet'    => 20,
        'razer.tha_truemoney' => 18,
        'razer.tha_one2call'  => 18,
        'razer.tha_pin'       => 18,
        'razer.tha_wallet'    => 20,
        'razer.vnm_wallet'    => 25,
        'razer.vnm_pin'       => 25,
        'razer.sau_wallet'    => 20,
        'razer.usa_pin'       => 20,
        'razer.nzl_pin'       => 25,
        'razer.qat_pin'       => 20,
        'razer.kwt_pin'       => 20,
        'razer.irq_pin'       => 20,
        'razer.bhr_pin'       => 20,
        'razer.jor_pin'       => 20,
        'razer.lbn_pin'       => 20,
        'razer.omn_pin'       => 20,
        'razer.mar_pin'       => 20,
        'razer.egy_pin'       => 20,
        'razer.lby_pin'       => 20,
        'razer.tun_pin'       => 20,
        'razer.dza_pin'       => 20,
        'razer.syr_pin'       => 20,
        'razer.yem_pin'       => 20,
        'razer.bra_pin'       => 20,
        'razer.aus_pin'       => 25,
        'razer.phl_pin'       => 20,
        'razer.ind_pin'       => 20,
        'razer.eur_pin'       => 20,
        'razer.mmr_pin'       => 20,
        'razer.tur_pin'       => 20,
        'razer.mex_pin'       => 20,
        'razer.can_pin'       => 20,

        'mentwall.kor_card'     => 4.25,
        'mentwall.kor_culture'  => 10.9,
        'mentwall.kor_happy'    => 9.2,
        'mentwall.kor_book'     => 11.2,
        'mentwall.kor_teencash' => 11,
        'mentwall.kor_cashbee'  => 8.7,
        'mentwall.kor_tmoney'   => 10.8,
        'mentwall.kor_oncash'   => 15.8,
        'mentwall.kor_eggmoney' => 16.9,

        'mycard.twn_linepay'   => 13,
        'mycard.twn_taiwanpay' => 13,
        'mycard.twn_jiekoupay' => 13,
        'mycard.twn_mycardpt'  => 24,
        'mycard.twn_twcard'    => 12,
        'mycard.twn_bank'      => 10,
        'mycard.twn_mycardwt'  => 25,

        'shareitpay.sau_card'   => 3.95,
        'shareitpay.are_card'   => 3.95,
        // 'shareitpay.kwt_knet' => 3.95,
        'shareitpay.kwt_card'   => 3.95,
        'shareitpay.qat_card'   => 3.95,
        'shareitpay.omn_card'   => 3.95,
        // 'shareitpay.bhr_benefit' => 3.95,
        'shareitpay.bhr_card'   => 3.95,
        'shareitpay.jor_card'   => 3.95,
        'shareitpay.lbn_card'   => 3.95,
        'shareitpay.egy_card'   => 3.95,
        // 'shareitpay.egy_fawry' => 3.95,
        'adyen.any_card'        => 5,
        'mycard.twn_youyoupay'  => 13,
        'mycard.twn_xinshiji'   => 19,
        'mycard.twn_yuanchuan'  => 19,
        'mycard.twn_twdgd'      => 19,
        'mycard.twn_zhonghuadx' => 19,
        'mycard.twn_yataidx'    => 19,
        'mycard.twn_twzx'       => 19,

        'coda.tha_dolfin'             => 7,
        'coda.tha_shopeepay'          => 10,
        'coda.sgp_paynow'             => 4,
        'coda.are_payit'              => 7,
        'coda.twn_linepay'            => 7,
        'coda.hkg_payme'              => 5.5,
        'coda.egy_vodafone'           => 6,
        'mentwall.any_onecard'        => 7.25,
        'shareitpay.twn_jiekou'       => 13,
        'shareitpay.twn_bank'         => 8.5,
        'shareitpay.twn_mcwallet'     => 13,
        'shareitpay.egy_mobliewt'     => 3.95,
        'shareitpay.idn_ovo'          => 3.9,
        'shareitpay.idn_doku'         => 2.5,
        'shareitpay.idn_dana'         => 2.9,
        'shareitpay.idn_linkaja'      => 2.9,
        'shareitpay.idn_indomaret'    => 12,
        'shareitpay.idn_akulaku'      => 3,
        'shareitpay.idn_shopeepay'    => 4.5,
        //		'shareitpay.tha_truemw' => 10,
        'shareitpay.tha_bank'         => 3.95,
        'shareitpay.tha_prompt'       => 3.95,
        'shareitpay.tha_airpay'       => 8,
        'shareitpay.tha_dolfin'       => 8,
        'shareitpay.tha_711'          => 8,
        'shareitpay.twn_mcwalletfree' => 13,
        'shareitpay.mys_grabpay'      => 2.9,
        'shareitpay.mys_touch'        => 2.9,
        'shareitpay.vnm_atm'          => 9,
        'shareitpay.vnm_nlwallet'     => 9,
        'shareitpay.tur_card'         => 3,
        'p99.twn_card'                => 12,
        'p99.twn_ysbank'              => 12,
        'p99.twn_gashpt'              => 22,
        'shareitpay.vnm_bank'         => 9,
        'yiyunh.vnm_bankbidv'         => 6.5,
        'yiyunh.vnm_bankmb'           => 6.5,
        'yiyunh.vnm_bankstb'          => 6.5,
        'yiyunh.vnm_bankvcb'          => 6.5,
        'yiyunh.vnm_bankvib'          => 6.5,
        'yiyunh.vnm_bankvtb'          => 6.5,
        'yiyunh.vnm_momo'             => 6.5,
    );

    public static function getChanelAndIdMapCache($clear = false)
    {
        static $_chanelAndIdMapcache = null;

        if (true === $clear) {
            $_chanelAndIdMapcache = null;
            return [];
        }

        if (empty($_chanelAndIdMapcache)) {
            $_chanelAndIdMapcache = XsIapConfig::getChanelAndIdMap();
        }

        return $_chanelAndIdMapcache;
    }

    public static function getSubjectByExchange($exchange)
    {
        $exchange = intval($exchange);
        $subject = $exchange * 10 . 'Diamonds';
        $description = $exchange * 10 . 'Diamonds';
        $subject10 = $exchange * 100 . 'Gold coin';
        $subject11 = $exchange * 20 . 'Gold bean';
        return array(
            'subject'     => $subject,
            'description' => $description,
            'subject_10'  => $subject10,
            'subject_11'  => $subject11,
        );
    }

    public static function getAppSubjectByExchange($exchange)
    {
        return $exchange . 'Diamonds';
    }

    /**
     * 获取商品对应的币数(分)
     * @param $id
     */
    public static function getProductExchange($id)
    {
        $id = intval($id);
        if ($id < 1) return 0;

        $redis = new RedisHash();
        $exchange = $redis->get(self::PRODUCT_EXCHANGE_CACHE, $id);
        if (!$exchange) {
            $model = self::findFirst($id);
            if (!$model) return 0;
            $exchange = $model->exchange;
            $redis->set(self::PRODUCT_EXCHANGE_CACHE, $id, $exchange);
        }
        return intval($exchange);
    }

    // 查询币种下的产品
    public static function getProductByCurrency($currency)
    {
        return self::find(['currency = :currency: and state = 1', 'bind' => ['currency' => $currency], 'columns' => 'id,product_id,price,exchange,channel_rate']);
    }

    // 建议币数/分
    public static function calcSugIcon($price, $rate, $channel_rate, $vat_rate)
    {
        return $rate ? bcdiv(bcmul($price * (10000 - $channel_rate) * (10000 - $vat_rate), $rate), 10000000) : '';
    }

    // fomo信用卡定价id
    public static function fomoCardId()
    {
        $res = self::find([
            "channel in ('fomo.any_card', 'fomo.sgp_card', 'fomo.jpn_card') and app = '" . PAY_APP_NAME . "'",
            'columns' => 'id',
        ])->toArray();
        return array_column($res, 'id');
    }

    public static function getAllPsIap($columns = '*')
    {
        $iap = self::find(["app = 'ps'", 'column' => $columns])->toArray();
        return array_column($iap, null, 'id');
    }
}