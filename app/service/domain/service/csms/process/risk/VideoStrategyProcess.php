<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk;

use Imee\Service\Domain\Service\Csms\Context\Risk\VideoProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\Video\RecommendVideoScan;


class VideoStrategyProcess
{
    public const MODE_RECOMMEND = 'recommend';

    private static $strategyCfg = [
        self::MODE_RECOMMEND => RecommendVideoScan::class,
    ];

    protected $context;

    public function __construct(VideoProxyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        return new self::$strategyCfg[$this->context->mode]($this->context);
    }
}