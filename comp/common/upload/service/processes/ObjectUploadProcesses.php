<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Comp\Common\CDN\CDN;
use Imee\Comp\Common\Log\LoggerProxy;
use Imee\Comp\Common\Upload\Service\Context\ObjectUploadContext;
use Imee\Exception\ApiException;
use Imee\Service\Helper;

class ObjectUploadProcesses extends AbstractUploadProcess
{
    public function __construct(ObjectUploadContext $context)
    {
        parent::__construct($context);
    }

    protected function doing()
    {
        $filePath = $this->moveFile();
        $url = Helper::getHeadUrl($filePath, false, $this->bucket);

        try {
            if ($this->context->refreshCaches) {
                CDN::getInstance()->refreshObjectCaches([
                    'objectPath' => $url,
                ]);
            }
        } catch (\Exception $e) {
            LoggerProxy::instance()->error($e->getMessage());
        }

        return [
            'url'  => $url,
            'name' => $filePath,
        ];
    }

    /**
     * @throws ApiException
     */
    protected function before()
    {
        $this->verify();
    }

    protected function getRemoteName(): string
    {
        return md5($this->context->content).'.txt';
    }

    protected function remoteFile()
    {
        $path = trim($this->context->path, '/');
        if (empty($path)) {
            $path = sprintf('object/%s/%s', date('Ym/d/'), $this->getRemoteName());
        }

//        if ($this->context->isDelete) {
//            $this->uploadClient->delete($path);
//        }
        $hasUploadSuccess = $this->uploadClient->putObject($path, $this->context->content);
        if (!$hasUploadSuccess) {
            LoggerProxy::instance()->error($this->uploadClient->getError());
            throw new ApiException(ApiException::UPLOAD_ERROR, $this->uploadClient->getError());
        }

        return $path;
    }

    protected function verify()
    {
//        if (empty($this->context->path)) {
//            throw new ApiException(ApiException::UPLOAD_ERROR, '上传object保存路径必指定');
//        }

        if (empty($this->context->content)) {
            throw new ApiException(ApiException::UPLOAD_ERROR, '上传object不能为空');
        }

        $size = mb_strlen($this->context->content);
        if ($this->context->allowFileSize > 0) {
            if ($this->context->allowFileSize > ObjectUploadContext::MAX_ALLOW_FILE_SIZE) {
                throw new ApiException(ApiException::UPLOAD_ERROR, sprintf('allowFileSize配置项大小不能超过 %d'), ObjectUploadContext::MAX_ALLOW_FILE_SIZE);
            }
            if ($size > $this->context->allowFileSize) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '上传object的大小超过了allowFileSize配置项');
            }
        } else {
            if ($size > ObjectUploadContext::MAX_ALLOW_FILE_SIZE) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '上传object的大小超过了最大上传限制');
            }
        }
    }
}
