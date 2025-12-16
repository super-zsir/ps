<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk;

use Imee\Service\Domain\Service\Csms\Context\Risk\ImageProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\Image\Scan\RecommendImageScan;

class ImageStrategyProcess
{
    public const MODE_ALI = 'lvwang';
    public const MODE_RECOMMEND = 'recommend';

    private static $strategyCfg = [
        // self::MODE_ALI => ImageScan::class,
        self::MODE_RECOMMEND => RecommendImageScan::class,
    ];

    protected $context;

    public function __construct(ImageProxyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        return new self::$strategyCfg[$this->context->mode]($this->context);
    }
}
