<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Video;

use Imee\Comp\Common\Sdk\SdkVideoService;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\XssCircleVideo;
use Imee\Service\Domain\Service\Csms\Context\Risk\VideoProxyContext;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;

class RecommendVideoScan
{
    use SingletonTrait;
    use CsmswarningTrait;

    /**
     * @var VideoProxyContext
     */
    public $context;

    private $result;

    public function __construct(VideoProxyContext $context)
    {
        $this->context = $context;
        $this->result = [
            'machine' => CsmsAudit::MACHINE_UNKNOWN
        ];
        $this->init();
    }

    /**
     * @return false|void
     */
    public function init()
    {
        try {
            /** @var SdkVideoService $obj */
            $obj = factory_single_obj(SdkVideoService::class);
            $condition = [];
            if (!$this->context->path) {
                return false;
            }
            if (!is_array($this->context->path)) {
                $this->context->setParams(array(
                    'path' => [$this->context->path]
                ));
            }
            foreach ($this->context->path as $item) {
                if (empty($item)) {
                    continue;
                }

                $result = $obj->post(SdkVideoService::VIDEO, array(
                    'video_url' => $item,
                    'task_id' => $this->context->dataId,
                ));
                if ($result && isset($result['code']) && $result['code'] == 200) {
                    $video_image = isset($result['result']['video_image']) ? json_encode($result['result']['video_image']) : '';
                    $label = $result['result']['label'] ?? '';
                    $video_url = $result['result']['video_url'] ?? '';
                    $condition[] = array(
                        'video_image' => $video_image,
                        'label' => $label,
                        'video_url' => $video_url,
                        'task_id' => $this->context->dataId,
                    );
                    $res = $label != 'normal' ? CsmsAudit::MACHINE_REFUSE : CsmsAudit::MACHINE_PASS;
                    foreach ($this->context->scenes as $scene) {
                        if (isset($this->result[$scene]) && $this->result[$scene]['machine'] != $res) {
                            // 若多视频，不同结果，取消机审代替人审检测
                            unset($this->result[$scene]);
                            break;
                        }
                        $this->result[$scene] = array(
                            'machine' => $res
                        );
                    }
                    if ($label != 'normal') {
                        $this->result['machine'] = CsmsAudit::MACHINE_REFUSE;
                        break;
                    } else {
                        $this->result['machine'] = CsmsAudit::MACHINE_PASS;
                    }
                } else {
                    $this->warning($result, $item, $this->context->dataId);
                }
            }
            if ($condition) {
                XssCircleVideo::addBatch($condition);
            }
        } catch (\Exception $e) {
            $result = [$e->getMessage().$e->getTraceAsString()];
            $this->warning($result, json_encode($this->context->path), $this->context->dataId);
        }
    }

    public function getResult()
    {
        return $this->result;
    }

    public function warning(array $result, $videoLink, $taskId)
    {
        $wecontent = <<<STR
推荐组视频检测接口异常
> 视频: {content}
> 推荐组RESULT: {result}
> TASKID: {taskid}
> 操作时间:{create_time}
STR;
        $wechatMsg = str_replace(
            ['{content}', '{result}', '{taskid}', '{create_time}'],
            [$videoLink ?? 'no url', json_encode($result, JSON_UNESCAPED_UNICODE), $taskId, date('Y-m-d H:i:s')],
            $wecontent
        );
        $this->sendRecommend($wechatMsg);
    }
}