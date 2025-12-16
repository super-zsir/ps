<?php

namespace Imee\Comp\Common\Upload\Service\Context;

/**
 * 图片上传
 */
class ImageUploadContext extends UploadBaseContext
{
    /**
     * @var string 图片尺寸 比列
     */
    protected $ratio;
    /**
     * @var string 图片尺寸 宽
     */
    protected $width;
    /**
     * @var string 图片尺寸 高度
     */
    protected $height;
    /**
     * @var string 图片尺寸 宽*高度
     */
    protected $imageRatio;
}
