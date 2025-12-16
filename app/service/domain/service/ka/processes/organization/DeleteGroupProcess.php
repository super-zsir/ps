<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Exception\Ka\OrgException;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\DeleteGroupContext;

class DeleteGroupProcess
{
    /**
     * @var DeleteGroupContext
     */
    private $context;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;

    /**
     * @var XsstKaOrganization
     */
    private $orgUserModel = XsstKaOrganizationUser::class;


    public function __construct(DeleteGroupContext $context)
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

        // 删除所有子部门
        $orgIds = $this->orgModel::findAllIdsById($id);

        if (!empty($orgIds)) {
            // 解除所有用户绑定
            $users = $this->orgUserModel::query()
                ->where('org_id IN ({org_id:array})', ['org_id' => $orgIds])
                ->execute();

            if ($users->valid()) {
                OrgException::throwException(OrgException::ORG_USER_EXIST_NOT_DELETE);
            }
        }

        $info->delete();

        // 删除所有子部门
        $this->orgModel::deleteByPath($id);

        return [];
    }
}