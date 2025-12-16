<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Image\Scan;

use Imee\Comp\Common\Sdk\SdkImageScan;
use Imee\Models\Xs\XsApp;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsImageScan;
use Imee\Service\Domain\Service\Csms\Context\Risk\ImageProxyContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class RecommendImageScan extends AbstractImageScan
{
    use CsmsTrait;

    public const NORMAL = 'pass';
    public const REVIEW = 'review';
    public const BLOCK = 'block';


    /**
     * 检测结果
     * @var string[]
     */
    public $result = [
        'machine' => CsmsAudit::MACHINE_IDENTIFY,
        'tags' => '',
        'reason' => ''
    ];


    public function __construct(ImageProxyContext $context)
    {
        parent::__construct($context);
    }



    public function init()
    {
        // 内容为空
        if(!$this->context->path){
            return false;
        }

        if(is_array($this->context->path)){
            foreach ($this->context->path as $path){
                $sdkScan = new SdkImageScan();
                $data = $sdkScan->newValidPorn(
                    $this->context->choice,
                    $this->context->pkValue,
                    $this->getChoiceImage($path, $this->context->toArray()),
                    $this->context->dataId,
                    $this->context->scenes
                );
                // 记录日志
                $logParams = [
                    'taskid' => $this->context->dataId,
                    'choice' => $this->context->choice,
                    'pk_value' => $this->context->pkValue,
                    'servicer' => 'recommend',
                    'url' => [$path],
                    'params' => $sdkScan->params,
                    'code' => $sdkScan->getLastCode(),
                    'msg' => $sdkScan->getLastError(),
                    'consume_time' => $sdkScan->consumeTime,
                    'results' => $data['data'] ?? [],
                ];
                if($data && isset($data['data']) && $data['data']) {
                    foreach ($data['data'] as $item) {
                        if (isset($item['results']) && $item['results']) {
                            $rec_result = array_column($item['results'], null,'scene');
                            foreach ($this->context->scenes as $scene) {
                                if (isset($rec_result[$scene]['suggestion'])) {
                                    switch ($rec_result[$scene]['suggestion']) {
                                        case self::NORMAL:
                                            $machine = 1;
                                            break;
                                        case self::BLOCK:
                                            $machine = 2;
                                            break;
                                        default:
                                            $machine = 3;
                                            break;
                                    }
                                    if (isset($this->result[$scene]) && $this->result[$scene]['machine'] != $machine) {
                                        // 一个字段多图，若检测结果不同，则取消机审代替人审操作
                                        unset($this->result[$scene]);
                                        break 2;
                                    }
                                    $this->result[$scene] = array(
                                        'machine' => $machine,
                                    );
                                }
                            }
                        }
                    }
                }
                // 这是多张图 返回结果
                if($data && isset($data['data']) && $data['data']){
                    foreach ($data['data'] as $item){
                        if(isset($item['results']) && $item['results']){
                            // 是否严重违规 - 色情、涉政、二维码
                            if(in_array('porn', $this->context->scenes)){
                                $isPorn = $this->isPorn($item['results']);
                                if($isPorn){
                                    $this->result['machine'] = CsmsAudit::MACHINE_DANGER;
                                    $this->result['tags'] = ['porn'];
                                    $this->result['reason'] = '图片:色情';
                                    // 日志
                                    $logParams['machine'] = $this->result['machine'];
                                    $this->addScanLog($logParams);
                                    return true;
                                }
                            }
                            // 涉政
                            if(in_array('terrorism', $this->context->scenes)){
                                $isTerrorism = $this->isTerrorism($item['results']);
                                if($isTerrorism){
                                    $this->result['machine'] = CsmsAudit::MACHINE_DANGER;
                                    $this->result['tags'] = ['terrorism', $isTerrorism];
                                    $this->result['reason'] = '图片:涉政';
                                    // 日志
                                    $logParams['machine'] = $this->result['machine'];
                                    $this->addScanLog($logParams);
                                    return true;
                                }
                            }
                            // 二维码
                            if(in_array('qrcode', $this->context->scenes)){
                                $isQrcode = $this->isQrcode($item['results']);
                                if($isQrcode){
                                    $this->result['machine'] = CsmsAudit::MACHINE_DANGER;
                                    $this->result['tags'] = ['qrcode'];
                                    $this->result['reason'] = '图片:二维码';
                                    // 日志
                                    $logParams['machine'] = $this->result['machine'];
                                    $this->addScanLog($logParams);
                                    return true;
                                }
                            }
                            // 一般违规
                            $isReview = $this->isReview($item['results']);
                            if($isReview){
                                $this->result['machine'] = CsmsAudit::MACHINE_REFUSE;
                                $this->result['tags'] = [$isReview];
                                $this->result['reason'] = '图片:'.$isReview;
                                // 日志
                                $logParams['machine'] = $this->result['machine'];
                                $this->addScanLog($logParams);
                                return true;
                            }
                        }
                    }
                }
                // 日志
                $this->addScanLog($logParams);
            }

            // 全部识别完，如果都没问题，返回识别成功
            $this->result['machine'] = CsmsAudit::MACHINE_PASS;
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
                        foreach (parent::$disallowQrcode as $disallow){
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
                        foreach(parent::$allowQrcode as $allowLink){
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
                if($item['suggestion'] != 'pass'){
                    return $item['label'];
                }
            }
        }
        return false;
    }


    /**
     * 获取检测结果
     * @return string[]
     */
    public function getResult()
    {
        return $this->result;
    }


    /**
     * 添加日志
     * @param array $log
     */
    public function addScanLog($log = [])
    {
        $imageScan = new CsmsImageScan();
        $logData = [
            'servicer' => $log['servicer'] ?? '',
            'taskid' => $log['taskid'] ?? '',
            'choice' => $log['choice'] ?? '',
            'pk_value' => $log['pk_value'] ?? '',
            'url' => $log['url'] ? json_encode($log['url']) : '',
            'code' => $log['code'] ?? 0,
            'msg' => $log['msg'] ?? '',
            'params' => $log['params'] ? json_encode($log['params']) : '',
            'results' => $log['results'] ? json_encode($log['results']) : '',
            'consume_time' => $log['consume_time'] ?? '',
            'dateline' => time(),
            'machine' => $log['machine'] ?? 0,
        ];
        print_r($logData);
        $imageScan->save($logData);
    }
}
