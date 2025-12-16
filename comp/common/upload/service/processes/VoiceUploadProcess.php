<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Comp\Common\Upload\Service\Context\VoiceUploadContext;
use Imee\Service\Helper;

/**
 * 音频上传
 */
class VoiceUploadProcess extends AbstractUploadProcess
{
    protected $allowExt = ['amr', 'm4a', 'mp3', 'aac'];
    protected $allowMimeType = ['audio/amr', 'audio/mp4a-latm', 'audio/mpeg', 'audio/aac', 'audio/x-hx-aac-adts'];

    public function __construct(VoiceUploadContext $context)
    {
        parent::__construct($context);
    }

    protected function getRemoteName()
    {
        $path = "voice/" . date("Ym") . "/";
        if (!empty($this->context->path)) {
            $path = $this->context->path;
        }
        //兼容输入，去除两边反斜杠
        $path = trim($path, '/');
        $remoteName = date("ymdHis") . rand(10, 99) . "." . $this->context->file->getExtension();
        return $path . '/' . $remoteName;
    }

    protected function doing()
    {
        $fileName = $this->moveFile();

        $name = $fileName;
        if ($this->context->type == 'getDuration') {
            $duration = $this->getDurationByFile();
            $name .= '@' . $duration;
        }

        return [
            'url'  => Helper::getHeadUrl($fileName, false, $this->bucket),
            'name' => $name,
        ];
    }
}
