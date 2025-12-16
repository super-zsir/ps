<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk;


use Imee\Service\Domain\Service\Csms\Context\Risk\AudioProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\Audio\ShumeiAudioScan;

class AudioStrategyProcess
{
    public const MODE_SHUMEI = 'shumei_audio';

    private static $strategyCfg = [
        self::MODE_SHUMEI => ShumeiAudioScan::class,
    ];

    protected $context;

    public function __construct(AudioProxyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        return new self::$strategyCfg[$this->context->mode]($this->context);
    }
}