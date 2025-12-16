<?php

namespace Imee\Comp\Common\Upload\Service\Context;

class ObjectUploadContext extends BaseContext
{
    const MAX_ALLOW_FILE_SIZE = 5 * 1024 * 1024 * 1024; // b

    /**
     * @var string 映射的标识符
     */
    protected $action;
    /**
     * @var string 上传文件存放路径
     */
    protected $path;
    /**
     * @var int 文件大小限制，单位 b
     */
    protected $allowFileSize = 0;
    /**
     * @var string
     */
    protected $content;

    protected $isDelete = false;

    protected $refreshCaches = false;
}