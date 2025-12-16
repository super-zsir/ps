<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Exception\Ka\OrgException;
use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\CreateContext;
//use Imee\Service\Domain\Service\Ka\User\KfService;
use Imee\Service\Lesscode\Traits\Help\ValidationTrait;

class CreateProcess
{
    use ValidationTrait;

    /**
     * @var CreateContext
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

    public function __construct(CreateContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $res = [];
        $this->validation();

        $kfIdArr = $this->context->kfId;

        foreach ($kfIdArr as $kfId)
        {
            $kfInfo = $this->orgUserModel::getInfoByKfId($kfId);

            if (empty($kfInfo)) {
                $kfInfo = new $this->orgUserModel;
                $kfInfo->kf_id = (int) $kfId;
            }

            $kfInfo->org_id = (int) $this->context->orgId;
            $kfInfo->is_master = (int) $this->context->isMaster;
            $kfInfo->data_purview = (int) $this->context->dataPurview;
            $kfInfo->state = (int) $this->context->state;
            $kfInfo->right_pic_url = !empty($this->context->rightPicUrl) ? $this->context->rightPicUrl : '';
            $kfInfo->ac_line = !empty($this->context->acLine) ? $this->context->acLine : '';
            $kfInfo->ac_whats_app = !empty($this->context->acWhatsApp) ? $this->context->acWhatsApp : '';
            $kfInfo->ac_wechat = !empty($this->context->acWechat) ? $this->context->acWechat : '';
            $kfInfo->ac_kakao = !empty($this->context->acKakao) ? $this->context->acKakao : '';
            $kfInfo->ac_zalo = !empty($this->context->acZalo) ? $this->context->acZalo : '';
            $kfInfo->save();

            // 同步config表 todo 暂时去掉这个逻辑
//            (new KfService())->syncConfig(['kf_id' => (int) $kfId, 'admin_uid' => $this->context->adminUid ?? 0]);
        }


        return $res;
    }

    private function validation(): void
    {
        if (empty($this->context->orgId)) {
            OrgException::throwException(OrgException::GROUP_SELECT_REQUIRE);
        }

        if (empty($this->context->kfId)) {
            OrgException::throwException(OrgException::KF_SELECT_REQUIRE);
        }

        $this->validationFieldEnum('isMaster', '是否组长', $this->orgModel::IS_MASTER_MAP);
        $this->validationFieldEnum('dataPurview', '数据权限', $this->orgModel::DATA_PURVIEW_MAP);
    }
}