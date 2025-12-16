<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk\Text;

use Imee\Models\Xss\CsmsAudit;
use Imee\Service\Domain\Service\Csms\Context\Risk\TextProxyContext;
use Imee\Service\Domain\Service\Csms\Task\CsmsMachineService;
use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class SpamTextScan
{
    use CsmsTrait;

    protected $context;
    private $result;

    public function __construct(TextProxyContext $context)
    {
        $this->context = $context;
        $this->result = [
            'machine' => CsmsAudit::MACHINE_UNKNOWN
        ];
        $this->init();
    }

    public function init()
    {
        if (!$this->context->path) {
            return false;
        }
        if (!is_array($this->context->path)) {
            $this->context->setParams(array(
                'path' => [$this->context->path]
            ));
        }
        $texts = implode(" ", $this->context->path);
        if (empty($texts)) {
            return false;
        }
        $taskid = $this->context->dataId;
        $csms = array(
            'taskid'  => $taskid,
            'content' => $texts,
            'uid'     => $this->context->uid,
            'choice'  => $this->context->choice,
            'pkValue' => $this->context->pkValue,
            'scenes'  => $this->context->scenes,
        );
        $service = new CsmsMachineService();
        $result = $service->spam($csms);
        if ($result) {
            $this->result = $result;
        }
    }

    public function getResult()
    {
        return $this->result;
    }
}
