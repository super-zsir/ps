<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Exception\ApiException;
use Imee\Comp\Common\Upload\Service\Context\VideoUploadContext;
use Imee\Service\Helper;

/**
 * 视频上传
 */
class VideoUploadProcess extends AbstractUploadProcess
{
    protected $allowExt = ['mp4', 'm4v', 'riv'];
    protected $allowMimeType = ['video/mp4', 'video/x-m4v', 'application/octet-stream', 'video/quicktime'];
    protected $allowFileSize = 20480;

    public function __construct(VideoUploadContext $context)
    {
        parent::__construct($context);
    }

    protected function doing()
    {
        $fileName = $this->moveFile();

        if (ENV != 'dev') {
            $this->_createCover($fileName);
        }

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

    private function _createCover(string $fileName)
    {
        //s3不支持截图
        if ($this->uploadClient->s3Instance) {
            return;
        }

        ini_set('memory_limit', '256M');

        $i = 1;
        $content = '';
        while (!$content) {
            $cover = Helper::getHeadUrl($fileName, $this->context->videocoverlocal, $this->bucket);
            $cover .= '?x-oss-process=video/snapshot,t_1,f_jpg,w_1080,h_1620,m_fast,ar_auto';
            $timeout = [
                'http' => [
                    'timeout' => 10
                ]
            ];
            $ctx = stream_context_create($timeout);
            $tmp = @file_get_contents($cover, 0, $ctx);
            if ($i > 5) {
                break;
            }
            if ($tmp) {
                $content = $tmp;
            }
            usleep(1000);
            $i++;
        }
        if (!$content) {
            throw new ApiException(ApiException::VIDEO_SCREENSHOT_ERROR);
        }
        $hasUploadSuccess = $this->uploadClient->putObject($fileName . '.jpg', $content);
        if (!$hasUploadSuccess) {
            throw new ApiException(ApiException::UPLOAD_ERROR, $this->uploadClient->getError());
        }
    }
}
