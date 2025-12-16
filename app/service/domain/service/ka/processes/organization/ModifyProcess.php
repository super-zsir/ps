<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Exception\Ka\OrgException;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\ModifyContext;
//use Imee\Service\Domain\Service\Ka\User\KfService;
use Imee\Service\Lesscode\Traits\Help\ValidationTrait;

class ModifyProcess
{
    use ValidationTrait;

    /**
     * @var ModifyContext
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
    private $info;

    public function __construct(ModifyContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $res = [];
        $this->validation();

        $this->info->org_id = (int) $this->context->orgId;
        $this->info->is_master = (int) $this->context->isMaster;
        $this->info->data_purview = (int) $this->context->dataPurview;
        $this->info->state = (int) $this->context->state;
        $this->info->right_pic_url = !empty($this->context->rightPicUrl) ? $this->context->rightPicUrl : '';
        $this->info->ac_line = !empty($this->context->acLine) ? $this->context->acLine : '';
        $this->info->ac_whats_app = !empty($this->context->acWhatsApp) ? $this->context->acWhatsApp : '';
        $this->info->ac_wechat = !empty($this->context->acWechat) ? $this->context->acWechat : '';
        $this->info->ac_kakao = !empty($this->context->acKakao) ? $this->context->acKakao : '';
        $this->info->ac_zalo = !empty($this->context->acZalo) ? $this->context->acZalo : '';
        $this->info->save();

        // 同步config表 todo 暂时去掉这个逻辑
//        (new KfService())->syncConfig(['kf_id' => (int) $this->info->kf_id, 'admin_uid' => $this->context->adminUid ?? 0]);

        return $res;
    }

    private function validation(): void
    {
        if (empty($this->context->orgId)) {
            OrgException::throwException(OrgException::GROUP_SELECT_REQUIRE);
        }

        if (empty($this->context->id)) {
            OrgException::throwException(OrgException::ILLEGAL_ERROR);
        }

        $this->validationFieldEnum('isMaster', '是否组长', $this->orgModel::IS_MASTER_MAP);
        $this->validationFieldEnum('dataPurview', '数据权限', $this->orgModel::DATA_PURVIEW_MAP);

        $this->info = $this->orgUserModel::getInfoByKfId($this->context->id);

        if (empty($this->info)) {
            OrgException::throwException(OrgException::KF_NO_EXIST);
        }
    }
}