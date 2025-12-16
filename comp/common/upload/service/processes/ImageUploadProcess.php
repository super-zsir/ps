<?php

namespace Imee\Comp\Common\Upload\Service\Processes;

use Imee\Comp\Common\Upload\Service\Context\ImageUploadContext;
use Imee\Exception\ApiException;
use Imee\Service\Helper;

/**
 * 图片上传
 */
class ImageUploadProcess extends AbstractUploadProcess
{
    protected $allowExt = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
    protected $allowMimeType = ['image/gif', 'image/jpeg', 'image/png', 'image/webp'];
    protected $allowFileSize = 2048;

    public function __construct(ImageUploadContext $context)
    {
        parent::__construct($context);
    }

    protected function doing()
    {
        list($width, $height) = $this->checkImageSize();

        $fileName = $this->moveFile();
        //暂不考虑特殊的，如需特殊的需要扩展
        return [
            'url'    => Helper::getHeadUrl($fileName, false, $this->bucket),
            'name'   => $fileName,
            'width'  => $width,
            'height' => $height,
        ];
    }

    private function checkImageSize()
    {
        list($width, $height) = @getimagesize($this->context->file->getTempName());

        // 所需宽高比
        if (!empty($this->context->ratio)) {
            $range = 0.1;
            $ratioWidthHeight = explode(':', $this->context->ratio);
            $needWidth = $ratioWidthHeight[0];
            $needHeight = $ratioWidthHeight[1];

            // 可接受宽高比范围
            $minRatio = $needWidth / $needHeight * (1 - $range);
            $maxRatio = $needWidth / $needHeight * (1 + $range);

            // 检测宽高比
            $actualRatio = $width / $height;

            if ($actualRatio < $minRatio || $actualRatio > $maxRatio) {
                $msg = "图片宽高比不正常,宽高比为 $needWidth:$needHeight,需在 $minRatio 至 $maxRatio 范围内";
                throw new ApiException(ApiException::UPLOAD_ERROR, $msg);
            }

            return [$width, $height];
        }

        if (!empty($this->context->width)) {
            if ($width != $this->context->width) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '图片需为：' . $this->context->width . 'x' . $this->context->height);
            }
        }
        if (!empty($this->context->height)) {
            if ($height != $this->context->height) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '图片需为：' . $this->context->width . 'x' . $this->context->height);
            }
        }
        if (!empty($this->context->imageRatio)) {
            //默认尺寸为 宽*高 多个用,分隔
            if (!in_array($width . '*' . $height, explode(',', trim($this->context->imageRatio)))) {
                throw new ApiException(ApiException::UPLOAD_ERROR, '图片尺寸需为：' . $this->context->imageRatio);
            }
        }

        return [$width, $height];
    }
}
