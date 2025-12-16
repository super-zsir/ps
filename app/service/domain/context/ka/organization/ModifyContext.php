<?php

namespace Imee\Service\Domain\Context\Ka\Organization;

use Imee\Service\Domain\Context\BaseContext;
use Imee\Service\Domain\Context\Traits\AdminUidContextTrait;

class ModifyContext extends BaseContext
{
    use AdminUidContextTrait;

    /**
     * @var int id
     */
    protected $id;

    /**
     * @var int 分组id
     */
    protected $groupId;

    /**
     * @var int 分组id
     */
    protected $orgId;

    /**
     * @var int 是否组长
     */
    protected $isMaster;

    /**
     * @var int 数据权限
     */
    protected $dataPurview;

    /**
     * @var int 是否启用
     */
    protected $state;

    /**
     * @var string 弹窗权益图
     */
    protected $rightPicUrl;

    /**
     * @var string line账号
     */
    protected $acLine;

    /**
     * @var string whats_app账号
     */
    protected $acWhatsApp;

    /**
     * @var string wechat账号
     */
    protected $acWechat;

    /**
     * @var string kakao账号
     */
    protected $acKakao;

    /**
     * @var string zalo账号
     */
    protected $acZalo;
}