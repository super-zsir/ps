<?php

namespace Imee\Comp\Common\Upload\Service;

use Imee\Comp\Common\Upload\Service\Context\BaseContext;
use Imee\Comp\Common\Upload\Service\Processes\ObjectUploadProcesses;
use Imee\Exception\ApiException;
use Imee\Comp\Common\Upload\Service\Context\UploadBaseContext;
use Imee\Comp\Common\Upload\Service\Processes\ImageUploadProcess;
use Imee\Comp\Common\Upload\Service\Processes\VideoUploadProcess;
use Imee\Comp\Common\Upload\Service\Processes\VoiceUploadProcess;
use Imee\Comp\Common\Upload\Service\Processes\FileUploadProcess;

/**
 * 上传服务
 */
class UploadService
{
    private $context;

    public function __construct(BaseContext $context)
    {
        $this->context = $context;
    }

    public const ACTION_IMAGE = 'image';
    public const ACTION_VIDEO = 'video';
    public const ACTION_VOICE = 'voice';
    public const ACTION_FILE = 'file';
    public const ACTION_OBJECT = 'object';
    private $displayAction = [
        self::ACTION_IMAGE => ImageUploadProcess::class,
        self::ACTION_VIDEO => VideoUploadProcess::class,
        self::ACTION_VOICE => VoiceUploadProcess::class,
        self::ACTION_FILE  => FileUploadProcess::class,
        self::ACTION_OBJECT => ObjectUploadProcesses::class,
    ];

    public function handle()
    {
        $this->verify();
        $className = $this->displayAction[$this->context->action];
        $processClass = (new \ReflectionClass($className))->newInstanceArgs(['context' => $this->context]);
        return $processClass->handle();
    }

    private function verify()
    {
        if (!isset($this->displayAction[$this->context->action])) {
            throw new ApiException(ApiException::ACTION_NOEXIST_ERROR);
        }

        if ($this->context instanceof UploadBaseContext) {

            if (!$this->context->request->hasFiles()) {
                throw new ApiException(ApiException::NO_UPLOAD_ERROR);
            }

            $files = $this->context->request->getUploadedFiles();
            $file = $files[0];

            if (!$file->isUploadedFile()) {
                throw new ApiException(ApiException::SOURCE_UNIDENTIFIED_ERROR);
            }

            $this->context->setParams([
                'file' => $file
            ]);
        }

    }
}
