<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Comp\Common\Upload\Service\Context\FileUploadContext;
use Imee\Helper\Traits\UploadFileTrait;
use Imee\Service\Helper;

/**
 * 文件上传
 */
class FileUploadProcess extends AbstractUploadProcess
{
    use UploadFileTrait;

    protected $allowExt = [
        'gif', 'jpg', 'jpeg', 'png', 'webp',
        'mp4', 'm4v', 'amr', 'm4a', 'mp3',
        'txt', 'xls', 'xlsx', 'csv',
        'zip', 'json', 'doc', 'docx',
        'pdf', 'lrc'
    ];
    protected $allowMimeType = [
        'image/gif', 'image/jpeg', 'image/png', 'image/webp',
        'video/mp4', 'video/x-m4v', 'audio/amr', 'audio/mp4a-latm', 'audio/mpeg',
        'text/plain', 'application/vnd.ms-excel', 'application/csv', 'text/csv',
        'application/zip', 'text/json', 'application/msword', 'application/octet-stream',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/pdf',
        'audio/x-wav', 'audio/wav'
    ];
    protected $allowFileSize = 20480;

    public function __construct(FileUploadContext $context)
    {
        parent::__construct($context);
    }

    protected function getRemoteName()
    {
        //解析type调用自定义方法
        if (!empty($this->context->type)) {
            $type = $this->context->type;
            $tmp = explode(':', $type);
            $function = $tmp[0];
            $type = $tmp[1] ?? '';
            return $this->$function($this->context, $type);
        }
        $path = "file/" . date("Ym") . "/";
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
        if ($this->context->duration == 1) {
            $duration = $this->getDurationByFile();
            $name .= '@' . $duration;
        }

        return [
            'url'  => Helper::getHeadUrl($fileName, false, $this->bucket),
            'name' => $name,
        ];
    }
}
