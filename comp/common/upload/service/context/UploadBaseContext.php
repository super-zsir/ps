<?php

namespace Imee\Comp\Common\Upload\Service\Context;

/**
 * 上传类基类
 */
class UploadBaseContext extends BaseContext
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var string 映射的标识符
     */
    protected $action;
    /**
     * @var 上传文件
     */
    protected $file;
    /**
     * @var string 上传文件存放路径
     */
    protected $path;
    /**
     * @var string 存储空间
     */
    protected $bucket;
    /**
     * @var string endpoint
     */
    protected $endpoint;
    /**
     * @var int 文件大小
     */
    protected $allowFileSize;
    /**
     * @var string 文件扩展
     */
    protected $allowExt;
    /**
     * @var string 操作类型 （根据此参数动态组装上传路径和控制上传大小）例：commodity:header_union
     */
    protected $type;
    /**
     * @var 视频封面截取使用内网域名
     */
    protected $videocoverlocal = true;
}
