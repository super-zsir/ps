<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Audio;

use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\AudioService;
use Imee\Service\Domain\Service\Csms\Context\Risk\AudioProxyContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;

class ShumeiAudioScan
{
    use SingletonTrait;
    use CsmswarningTrait;

    /**
     * @var AudioProxyContext
     */
    public $context;

    private $result = CsmsAudit::MACHINE_UNKNOWN;

    public function __construct(AudioProxyContext $context)
    {
        $this->context = $context;
        $this->init();
    }

    /**
     * @return false|void
     */
    public function init()
    {
        try {
            $audios = $this->context->path;
            if(!$audios){
                return false;
            }
            if (!is_array($audios)) {
                $this->context->setParams(array(
                    'path' => [$this->context->path]
                ));
            }
            foreach ($audios as $audio){
                if (empty($audio)) {
                    continue;
                }
                $audioData = [
                    'choice' => $this->context->choice,
                    'taskid' => $this->context->dataId,
                    'dataid' => md5($this->context->dataId.$audio),
                    'type' => 'url',
                    'content' => $audio,
                    'pk' => $this->context->pkValue
                ];

                $audioService = new AudioService();
                $result = $audioService->upload($audioData);
                if (!$result) {
                    $this->warning(['msg' => '推送csms.audio::audio.upload失败','audioData' => $audioData], json_encode($audio), $this->context->dataId);
                }
            }
        } catch (\Exception $e) {
            $result = [$e->getMessage().$e->getTraceAsString()];
            $this->warning($result, json_encode($audios), $this->context->dataId);
        }
    }

    public function getResult()
    {
        return $this->result;
    }

    public function warning(array $result, $link, $taskId)
    {
        $wecontent = <<<STR
数美音频检测接口异常
> 音频: {content}
> 数美RESULT: {result}
> TASKID: {taskid}
> 操作时间:{create_time}
STR;
        $wechatMsg = str_replace(
            ['{content}', '{result}', '{taskid}', '{create_time}'],
            [$link ?? 'no url', json_encode($result, JSON_UNESCAPED_UNICODE), $taskId, date('Y-m-d H:i:s')],
            $wecontent
        );
        $this->sendCsms($wechatMsg);
    }
}