<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Exception\Ka\OrgException;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Service\Domain\Context\Ka\Organization\ModifyGroupContext;

class ModifyGroupProcess
{
    /**
     * @var ModifyGroupContext
     */
    private $context;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;


    public function __construct(ModifyGroupContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $id   = (int) $this->context->id;
        $info = $this->orgModel::findFirstById($id);

        if (empty($info)) {
            OrgException::throwException(OrgException::NO_DATA_ERROR);
        }

        $orgName = addslashes(trim($this->context->orgName));
        if ($info->org_name != $orgName) {
            $info->org_name = $orgName;
        }

        $weight = (int) $this->context->weight;
        if ($info->weight != $weight) {
            $info->weight = $weight;
        }

        if ($info->getChangedFields()) {
            $info->save();
        }

        return [];
    }
}