<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Service\Domain\Context\Ka\Organization\CreateGroupContext;

class CreateGroupProcess
{
    /**
     * @var CreateGroupContext
     */
    private $context;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;


    public function __construct(CreateGroupContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $info = new $this->orgModel;
        $info->pid = (int) $this->context->pid;
        $info->org_name = addslashes(trim($this->context->orgName));
        $info->weight = (int) $this->context->weight;
        $info->save();

        // 更新path
        $info::updatePath($info->id);

        return [];
    }
}