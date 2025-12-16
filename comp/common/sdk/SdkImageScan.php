<?php

namespace Imee\Comp\Common\Sdk;

use Imee\Models\Xs\XsApp;
use Imee\Service\Helper;
//use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

/**
 * 推荐组 图片审核
 * Class SdkImageScan
 */
class SdkImageScan extends SdkBase
{
//    use CsmsTrait;

    private $domain = 'http://' . Serv_Spam_Image_Name;
    private $_imageScanDomain = 'http://' . Serv_Image_Scan;
    // 接口参数
    public $params = '';
    // 接口耗时
    public $consumeTime = 0;


    public static $disallowQrcode = array(
        'EImVXNl5GTJz6nwf8NnsbaI',
        'MMAtFUVpn_Zd2MYdXiZ5M7c',
        'MIpdwGvbLpyvRQKpavV3lCE'
    );


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



    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
        $this->setContentType('application/json');
    }

    /**
     * 图片检测
     * @params type 用于是哪个业务
     * image.valid.porn  用户头像
     */
    public function validPorn($path, $uid, $dataId = '', $scenes = ['qrcode', 'ocr'])
    {
        date_default_timezone_set("Asia/Shanghai");

        $paths = is_array($path) ? $path : [$path];
        $tasks = [];
        foreach ($paths as &$v) {
            $v = Helper::getHeadUrl($v);
            $tasks[] = [
                'url'     => $v,
                'task_id' => $dataId ?? md5(uniqid())
            ];
        }

        if (!$scenes) {
            Helper::tasklog("[RecommendValid][Error][" . implode(',', $paths) . "][{$uid}] " . '检测场景类型错误');
            return null;
        }

        $jsonData = array(
            'tasks'  => $tasks,
            'scenes' => $scenes
        );
        $url = $this->domain . '/green/image/scan';
        try {
            if (ENV == 'dev') {
                $res = $this->dev([
                    'url'     => $path,
                    'uid'     => $uid,
                    'task_id' => $dataId,
                    'scenes'  => $scenes
                ]);
            } else {
                $res = $this->request($url, true, json_encode($jsonData));

                // 接口预警
                if (!in_array($this->_lastCode, $this->_allowCode)) {
                    $content = <<<STR
【Csms】推荐组图片检测服务异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 图片URL: {path}
> TASKID: {taskid}
> 操作时间:{create_time}
STR;
                    $wechatMsg = str_replace(
                        ['{url}', '{code}', '{error}', '{path}', '{taskid}', '{create_time}'],
                        [$url, $this->_lastCode, $this->_lastError, implode(',', $paths), $dataId, date('Y-m-d H:i:s')],
                        $content
                    );
                    /** @var SdkSlack $obj */
                    $obj = factory_single_obj(SdkSlack::class);
                    $webhook = ENV == 'dev' ? SLACK_KEFU_WEBHOOK : SLACK_RECOMMEND_WEBHOOK;
                    $obj->sendMsg($webhook, 'text', $wechatMsg);
                }
            }
            if ($res['code'] == 200) {
                $data = $res['data'];
                if (is_array($data) && count($data) > 0) {
                    // 请求成功，但数据错误
                    Helper::console("[RecommendValid][OK][" . implode(',', $paths) . "][{$uid}] " . json_encode($res));
                    // return new RecommendImageScan($rec['results'], $rec['task_id'], $res);
                    return $res;
                }
            } else {
                // 请求成功，但数据错误
                Helper::console("[RecommendValid][Error][" . implode(',', $paths) . "][{$uid}] " . json_encode($res));
            }
        } catch (\Exception $e) {
            Helper::console("[RecommendValid][Error][" . implode(',', $paths) . "][{$uid}] " . $e->getMessage());
        }

        return null;
    }

    /**
     * 已测试返回格式相同
     * @param $data
     * @return array
     */
    public function dev($data)
    {
        $results = [];

        $suggestion = [
            -1 => 'review',
            0  => 'pass',
            1  => 'block'
        ];

        $qrcode = [
            'http://www.ddyinjie.com',
            'http://www.baidu.com',
            'MMAtFUVpn_Zd2MYdXiZ5M7c',
            'http://www.51yinjie.com',
            'http://www.sohu.com',
            'http://www.qq.com',
        ];

        $ocr = [
            '快手',
            '小红书',
            '百度',
            '5637829547623',
            '你说呢',
            '陌陌',
            'hr235443jh5778kjj',
            'jkgfdg7et438272662882kfh',
            '毛爷爷'
        ];
        foreach ($data['scenes'] as $scene) {
            $one = [
                'scene' => $scene,
                'label' => array_rand($suggestion, 1)
            ];
            if ($scene == 'qrcode') {
                $keys = array_rand($qrcode, 3);
                foreach ($keys as $key) {
                    $one['qrcodeData'][] = $qrcode[$key];
                }
                $one['label'] = 1;
            }
            if ($scene == 'ocr') {
                $ocr_keys = array_rand($ocr, 3);
                foreach ($ocr_keys as $ocr_key) {
                    $one['ocr_words_result'][] = $ocr[$ocr_key];
                }
                $one['label'] = 1;
            }
            $one['suggestion'] = $suggestion[$one['label']];
            $results[] = $one;
        }
        return [
            'code' => 200,
            'data' => [
                [
                    'url'     => $data['url'] ?? '',
                    'task_id' => $data['task_id'] ?? '',
                    'results' => $results
                ]
            ]
        ];
    }

    /**
     * 图片检测
     * @params type 用于是哪个业务
     * image.valid.porn  用户头像
     */
    public function newValidPorn($type, $pk, $paths, $dataId = '', $scenes = [], $appId = APP_ID)
    {
        date_default_timezone_set("Asia/Shanghai");

        if (!$paths) {
            return null;
        }

        $bizType = '';
        $moduleTable = $type;

        $jsonData = array(
            'scenes' => $scenes,
            'source' => 'system_' . $appId
        );

        //支持单图检测
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        // 多图检测
        foreach ($paths as $path) {
//            if (!preg_match("/^http[s]{0,1}:\/\//i", $path)) {
//                $path = Helper::getHeadUrl($path);
//            }

            $path = $this->getCdnImageUrl($path, [
                'choice' => $type,
                'app_id' => $appId
            ]);

            $jsonData['tasks'][] = [
                'url'         => $path,
                'taskId'      => md5($path . uniqid()),
                'moduleTable' => $moduleTable
            ];
        }

        if (empty($jsonData['tasks'])) {
            return null;
        }

        if ($bizType) {
            $jsonData['bizType'] = $bizType;
        }
        $url = $this->_imageScanDomain . '/green/image/scan';

        try {
            $apiTime1 = microtime(true);
            if (ENV == 'dev') {
                $res = $this->newdev([
                    'url'     => $path,
                    'uid'     => $pk,
                    'task_id' => $dataId ?: md5(uniqid()),
                    'scenes'  => $scenes
                ]);
            } else {
                $res = $this->request($url, true, json_encode($jsonData));
                // 接口预警
                if (!in_array($this->_lastCode, $this->_allowCode)) {
                    $wecontent = <<<STR
【Csms】推荐组图片检测新接口异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 图片URL: {path}
> TASKID: {taskid}
> 操作时间:{create_time}
STR;
                    $wechatMsg = str_replace(
                        ['{url}', '{code}', '{error}', '{path}', '{taskid}', '{create_time}'],
                        [$url, $this->_lastCode, $this->_lastError, $path, $dataId, date('Y-m-d H:i:s')],
                        $wecontent
                    );
                    /** @var SdkSlack $obj */
                    $obj = factory_single_obj(SdkSlack::class);
                    $webhook = ENV == 'dev' ? SLACK_KEFU_WEBHOOK : SLACK_RECOMMEND_WEBHOOK;
                    $obj->sendMsg($webhook, 'text', $wechatMsg);
                }
            }

            $this->params = $jsonData;
            $apiTime2 = microtime(true);
            $apiConsumeTime = round($apiTime2 - $apiTime1, 3);
            $this->consumeTime = $apiConsumeTime;

            if ($res['code'] == 200) {
                $data = $res['data'];
                if (is_array($data) && count($data) > 0) {
                    // 请求成功，但数据错误
                    Helper::console("[RecommendValid][{$type}][OK][{$path}][{$pk}][{$apiConsumeTime}] " . json_encode($res));
                    return $res;
                }
            } else {
                // 请求成功，但数据错误
                Helper::console("[RecommendValid][{$type}][Error][{$path}][{$pk}] " . json_encode($res));
            }
        } catch (\Exception $e) {
            Helper::console("[RecommendValid][{$type}][Error][{$path}][{$pk}] " . $e->getMessage());
            $wecontent = <<<STR
【Csms】推荐组图片检测新接口(后端错误)
> URL: {url}
> CODE: {code}
> MESSAGE: {message}
> ERROR: {error}
> 图片URL: {path}
> TASKID: {taskid}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{message}', '{error}', '{path}', '{taskid}', '{create_time}'],
                [$url, $this->_lastCode, $e->getMessage(), $e->getTraceAsString(), $path, $dataId, date('Y-m-d H:i:s')],
                $wecontent
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }
        return null;
    }

    /**
     * 已测试返回格式相同
     * @param $data
     * @return array
     */
    public function newdev($data)
    {
        $results = [];

        $suggestion = [
            -1 => 'review',
            0  => 'pass',
            1  => 'block'
        ];

        $labels = [
            'porn'      => ['normal', 'sexy', 'porn'],
            'terrorism' => ['normal', 'bloody', 'explosion', 'outfit', 'logo', 'weapon', 'politics', 'violence', 'crowd', 'parade', 'flag'],
            'qrcode'    => ['normal', 'qrcode', 'programCode'],
            'ocr'       => ['normal', 'ad', 'porn', 'abuse', 'npx'],
            'violation' => ['smoking'],
            'ad'        => ['logo'],
            'bad'       => ['mask', 'tiktokscreenshot']
        ];

        $qrcode = [
            'http://www.ddyinjie.com',
            'http://www.baidu.com',
            'MMAtFUVpn_Zd2MYdXiZ5M7c',
            'http://www.51yinjie.com',
            'http://www.sohu.com',
            'http://www.qq.com',
        ];

        $ocr = [
            '快手',
            '小红书',
            '百度',
            '5637829547623',
            '你说呢',
            '陌陌',
            'hr235443jh5778kjj',
            'jkgfdg7et438272662882kfh',
            '毛爷爷'
        ];
        foreach ($data['scenes'] as $scene) {
            $one = [
                'scene' => $scene,
                'label' => $labels[$scene][array_rand($labels[$scene], 1)]
            ];
            $rand = array_rand($suggestion, 1);
            $one['suggestion'] = $suggestion[$rand];
            $one['rate'] = mt_rand(80, 100);

            if ($scene == 'qrcode') {
                $keys = array_rand($qrcode, 3);
                foreach ($keys as $key) {
                    $one['qrcodeData'][] = $qrcode[$key];
                }
            }
            if ($scene == 'ocr') {
                $ocr_keys = array_rand($ocr, 3);
                foreach ($ocr_keys as $ocr_key) {
                    $one['ocrData'][] = $ocr[$ocr_key];
                }
            }

            $results[] = $one;
        }
        return [
            'code' => 200,
            'msg'  => 'OK',
            'data' => [
                [
                    'code'    => 200,
                    'msg'     => 'OK',
                    'url'     => $data['url'] ?? '',
                    'taskId'  => $data['task_id'] ?? '',
                    'results' => $results
                ]
            ]
        ];
    }

    /**
     * 图片审核结果反馈
     */
    public function feedback($params = [])
    {
        $url = ENV == 'dev' ? 'http://192.168.11.46:4000/mock/425/image/accept' : $this->_imageScanDomain . '/image/accept';
        try {
            $log = [];
            $time1 = microtime(true);
            $res = $this->request($url, true, json_encode($params));
            $time2 = microtime(true);
            // 接口预警
            if (!in_array($this->_lastCode, $this->_allowCode)) {
                $content = <<<STR
推荐组图片审核结果反馈接口异常
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 图片URL: {path}
> TASKID: {taskid}
> PARAMS: {params}
> 审核结果: {result}
> 操作时间:{create_time}
STR;
                $wechatMsg = str_replace(
                    ['{url}', '{code}', '{error}', '{path}', '{taskid}', '{params}', '{result}', '{create_time}'],
                    [$url, $this->_lastCode, $this->_lastError, $params['url'], $params['taskId'], json_encode($params), $params['checkStatus'], date('Y-m-d H:i:s')],
                    $content
                );
                /** @var SdkSlack $obj */
                $obj = factory_single_obj(SdkSlack::class);
                $obj->sendMsg(SLACK_RECOMMEND_WEBHOOK, 'text', $wechatMsg);
            }

            if ($res) {
                $log = array_merge(
                    $params,
                    $res,
                    [
                        'logmark' => '推荐组审核结果反馈成功',
                        'consume' => round($time2 - $time1, 3)
                    ]
                );
                Helper::console($log);
            }
            return $log;
        } catch (\Exception $e) {
            $content = <<<STR
推荐组图片审核结果反馈接口异常ERROR
> URL: {url}
> 图片URL: {path}
> TASKID: {taskid}
> 审核结果: {result}
> MESSAGE:{message}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{path}', '{taskid}', '{result}', '{message}'],
                [$url, $params['url'], $params['taskId'], $params['checkStatus'], $e->getMessage()],
                $content
            );

            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
            return false;
        }
    }


    /**
     * 异步检测
     * @param array $params
     */
    public function asyncscan($params = [])
    {
        $url = $this->_imageScanDomain . '/green/image/asyncscan';
        try {

            $apiTime1 = microtime(true);
            if (ENV == 'dev') {
                return [
                    'code' => 200,
                    'msg'  => ''
                ];
            }

            $res = $this->request($url, true, json_encode($params));

            // 接口耗时
            $apiTime2 = microtime(true);
            $apiConsumeTime = round($apiTime2 - $apiTime1, 3);
            $this->consumeTime = $apiConsumeTime;

            // 接口预警
            if (!in_array($this->_lastCode, $this->_allowCode)) {
                $wecontent = <<<STR
推荐组异步图片检测
> URL: {url}
> CODE: {code}
> ERROR: {error}
> 请求参数: {params}
> 操作时间:{create_time}
STR;
                $wechatMsg = str_replace(
                    ['{url}', '{code}', '{error}', '{params}', '{create_time}'],
                    [$url, $this->_lastCode, $this->_lastError, json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
                    $wecontent
                );
                /** @var SdkSlack $obj */
                $obj = factory_single_obj(SdkSlack::class);
                $webhook = ENV == 'dev' ? SLACK_KEFU_WEBHOOK : SLACK_RECOMMEND_WEBHOOK;
                $obj->sendMsg($webhook, 'text', $wechatMsg);
            }
            return $res;
        } catch (\Exception $e) {
            $wecontent = <<<STR
推荐组异步图片检测
> URL: {url}
> CODE: {code}
> MESSAGE: {message}
> ERROR: {error}
> 请求参数: {params}
> 操作时间:{create_time}
STR;
            $wechatMsg = str_replace(
                ['{url}', '{code}', '{message}', '{error}', '{params}', '{create_time}'],
                [$url, $this->_lastCode, $e->getMessage(), $e->getTraceAsString(), json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
                $wecontent
            );
            /** @var SdkSlack $obj */
            $obj = factory_single_obj(SdkSlack::class);
            $obj->sendMsg(SLACK_KEFU_WEBHOOK, 'text', $wechatMsg);
        }
    }




    /**
     * 是否色情
     * @param bool $strict
     * @return bool
     */
    public function isPorn($data, $strict = false){
        foreach ($data as $item){
            if($item['scene'] == 'porn'){
                // 严格模式：色情直接删除，性感 >= 55 直接删除
                if($strict == true){
                    if($item['label'] == 'porn' || ($item['label'] == 'sexy' && $item['rate'] >= 55)){
                        return $item['label'];
                    }
                }else{
                    if($item['label'] == 'porn') return $item['label'];
                }
            }
        }
        return false;
    }

    /**
     * 是否涉政
     * @param array $data
     */
    public function isTerrorism($data = [])
    {
        foreach ($data as $item){
            if($item['scene'] == 'terorism'){
                if($item['suggestion'] == 'block') return $item['label'];
                if($item['suggestion'] == 'review') return $item['label'];
            }
        }
        return false;
    }


    /**
     * 二维码
     * @param array $data
     */
    public function isQrcode($data = [])
    {
        if(!$data) return false;
        foreach ($data as $item){
            if($item['scene'] == 'qrcode'){
                $qrcodeData = $item['qrcodeData'] ?? [];
                if($qrcodeData){
                    // 违禁二维码
                    foreach ($qrcodeData as $qrcode){
                        foreach (self::$disallowQrcode as $disallow){
                            if(strpos($qrcode, $disallow) !== false){
                                return true;
                            }
                        }
                    }
                    // 二维码白名单
                    foreach ($qrcodeData as $qrcode){
                        $pkgs = XsApp::getAllPkg();
                        if ($pkgs) {
                            foreach ($pkgs as $pkg){
                                if (strpos($qrcode, $pkg) !== false) {
                                    continue 2;
                                }
                            }
                        }

                        $inAllowList = false;
                        foreach(self::$allowQrcode as $allowLink){
                            if(strpos($qrcode, $allowLink) === 0){
                                $inAllowList = true;
                            }
                        }
                        if (!$inAllowList) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }


    /**
     * 人审
     * @param array $data
     */
    public function isReview($data = [])
    {
        if($data){
            foreach ($data as $item){
                if($item['suggestion'] != 'pass' && $item['scene'] != 'similar'){
                    return $item['scene'];
                }
            }
        }
        return false;
    }

    /**
     * 获取图片url
     * @param $url
     * @return string
     */
    public function getCdnImageUrl($url)
    {
        if (!$url) return "";
        if (preg_match('/(http|https):\/\/.*/is', $url)) {
            return $url;
        }
        if (ENV == 'dev') {
            return OSS_IMAGE_URL_TEST . '/' . $url;
        }
        return CDN_IMG_DOMAIN . $url;
    }
}
