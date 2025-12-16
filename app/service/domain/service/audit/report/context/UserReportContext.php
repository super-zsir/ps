<?php

namespace Imee\Service\Domain\Service\Audit\Report\Context;

use Imee\Service\Domain\Context\PageContext;

class UserReportContext extends PageContext
{
    /**
     * @var 状态
     */
    protected $state;

    /**
     * @var 类型
     */
    protected $type;

    /**
     * @var 举报人
     */
    protected $uid;

    /**
     * @var 被举报人
     */
    protected $to;

    /**
     * @var app
     */
    protected $appId;

    /**
     * @var 等级
     */
    protected $sxvip;


    /**
     * @var 被举报人等级
     */
    protected $sxvip2;

    /**
     * @var 语言
     */
    protected $language;

    /**
     * @var 房间id
     */
    protected $rid;

    /**
     * @var 房间ids
     */
    protected $rids;

    protected $id;

    protected $ids;

    protected $reason;

    protected $admin;

    protected $ridLg;

    protected $property;

    protected $fromBigArea;

    protected $datelineStart;

    protected $datelineEnd;
}