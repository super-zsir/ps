<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Exception\Ka\OrgException;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\DeleteContext;
//use Imee\Service\Domain\Service\Ka\User\KfService;
use Imee\Service\Lesscode\Traits\Help\ValidationTrait;

class DeleteProcess
{
    use ValidationTrait;

    /**
     * @var DeleteContext
     */
    private $context;

    /**
     * @var XsstKaOrganization
     */
    private $orgModel = XsstKaOrganization::class;

    /**
     * @var XsstKaOrganizationUser
     */
    private $orgUserModel = XsstKaOrganizationUser::class;

    /**
     * @var XsstKaOrganizationUser
     */
    private $list;

    public function __construct(DeleteContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $res = [];
        $this->validation();

        //$this->list->update(['org_id' => 0]);
        $this->list->delete();
        // 同步config表 只有数据删除了才同步 todo 暂时去掉这个逻辑
//        (new KfService())->syncConfig(['kf_id' => $this->context->id, 'admin_uid' => $this->context->adminUid]);

        return $res;
    }

    private function validation(): void
    {
        if (empty($this->context->id)) {
            OrgException::throwException(OrgException::ILLEGAL_ERROR);
        }

        $this->list = $this->orgUserModel::query()
            ->where('kf_id IN ({kf_id:array})', ['kf_id' => (array) $this->context->id])
            ->execute();

        if (!$this->list->valid()) {
            OrgException::throwException(OrgException::KF_NO_EXIST);
        }
    }
}