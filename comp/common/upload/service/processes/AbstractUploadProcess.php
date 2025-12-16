<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Comp\Common\Upload\Service\Context\BaseContext;
use Imee\Exception\ApiException;
use Imee\Comp\Common\Upload\Service\Context\UploadBaseContext;
use OSS\OssUpload;

/**
 * 上传
 */
abstract class AbstractUploadProcess
{
    protected $context;
    /**
     * @var OssUpload $uploadClient
     */
    protected $uploadClient;
    protected $allowMimeType = [];
    /**
     * 允许的文件大小，单位K
     */
    protected $allowFileSize;
    protected $allowExt;

    protected $bucket;

    public function __construct(BaseContext $context)
    {
        $this->context = $context;
        $this->allowFileSize = $context->allowFileSize ?: 0;
        if ($context->allowExt) {
            $this->allowExt = explode(',', $context->allowExt);
        }
        $this->getUploadClient();
    }

    private function getUploadClient()
    {
        if (!defined('BUCKET_DEV')) {
            throw new ApiException(ApiException::MSG_ERROR, 'config_define未定BUCKET_DEV');
        }
        if (!defined('BUCKET_ONLINE')) {
            throw new ApiException(ApiException::MSG_ERROR, 'config_define未定BUCKET_ONLINE');
        }

        if (!empty($this->context->bucket)) {
            $this->bucket = $this->context->bucket;
        } else {
            $this->bucket = BUCKET_ONLINE;
        }

        if (ENV == 'dev') {
            $this->bucket = BUCKET_DEV;
        }
        if (!empty($this->context->endpoint)) {
            $this->uploadClient = new OssUpload($this->bucket, $this->context->endpoint);
        } else {
            $this->uploadClient = new OssUpload($this->bucket);
        }
        
    }

    protected function before()
    {
        if ($this->context instanceof UploadBaseContext) {

            $ext = $this->context->file->getExtension();
            if (is_null($ext)) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '文件扩展名不能为空');
            }
            $mimeType = mime_content_type($this->context->file->getTempName());
            if (!$mimeType || !in_array($mimeType, $this->allowMimeType)) {
                throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
            }
            $ext = $this->context->file->getExtension();
            if (!in_array($ext, $this->allowExt)) {
                throw new ApiException(ApiException::EXTENSION_NOALLOW_ERROR);
            }

            if ($this->allowFileSize > 0 && $this->allowFileSize < bcdiv($this->context->file->getSize(), 1024)) {
                throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, $this->allowFileSize);
            }
        }
    }

    abstract protected function doing();

    public function handle()
    {
        $this->before();
        return $this->doing();
    }

    protected function getRemoteName()
    {
        $path = date('Ym/d/');
        if (!empty($this->context->path)) {
            $path = $this->context->path;
        }
        //兼容输入，去除两边反斜杠
        $path = trim($path, '/');
        $prefix = ip2long($_SERVER['SERVER_ADDR']);
        $ext = $this->context->file->getExtension();
        return $path . '/' . uniqid($prefix, true) . '.' . $ext;
    }

    protected function remoteFile()
    {
        if ($this->context instanceof UploadBaseContext) {
            $remoteName = $this->getRemoteName();
            $hasUploadSuccess = $this->uploadClient->moveFileTo($this->context->file->getTempName(), $remoteName);
            if (!$hasUploadSuccess) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '请查看错误日志');
            }
            return $remoteName;
        }
    }

    protected function moveFile()
    {
        return $this->remoteFile();
    }

    protected function getDurationByFile(): int
    {
        $path = $this->context->file->getTempName();
        if (file_exists($path)) {
            require_once __DIR__ . "/../getid3/getid3.php";
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($path);
            return $fileInfo['playtime_seconds'] ?? 0;
        }
        return 0;
    }
}
