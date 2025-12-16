<?php

namespace Imee\Service\Domain\Service\Csms\Process\Risk;

use Imee\Service\Domain\Service\Csms\Context\Risk\TextProxyContext;
use Imee\Service\Domain\Service\Csms\Context\Risk\VideoProxyContext;
use Imee\Service\Domain\Service\Csms\Process\Risk\Text\SensitiveTextScan;
use Imee\Service\Domain\Service\Csms\Process\Risk\Text\SpamTextScan;

class TextStrategyProcess
{
    public const MODE_SENSITIVE = 'sensitive';
    public const MODE_SPAM = 'spam';

    private static $strategyCfg = [
        self::MODE_SENSITIVE => SensitiveTextScan::class,
        self::MODE_SPAM => SpamTextScan::class,
    ];

    protected $context;

    public function __construct(TextProxyContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        return new self::$strategyCfg[$this->context->mode]($this->context);
    }
}