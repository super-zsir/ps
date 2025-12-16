<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\ListContext;
use Imee\Comp\Operate\Auth\Service\StaffService;
use Imee\Service\Helper;

class ListProcess
{
    /**
     * @var ListContext
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

    private $staffService;

    public function __construct(ListContext $context)
    {
        $this->context = $context;
        $this->staffService = new StaffService();
    }

    public function handle(): array
    {
        $res = [
            'data' => [],
            'total' => 0,
        ];

        $condition = [];

        if (is_numeric($this->context->groupId) && $this->context->groupId >= 0) {
            $condition = [
                ['org_id', '=', $this->context->groupId]
            ];
        }

        $page = $this->context->page ?? 1;
        $pageSize = $this->context->limit ?? 15;

        $res = $this->orgUserModel::getListAndTotal($condition, '*', $this->getOrderBy(), $page, $pageSize);
        $res['data'] = $this->formatList($res['data']);

        return $res;
    }

    private function getOrderBy()
    {
        if (empty($this->context->sort)) {
            return 'id desc';
        }

        if (empty($this->context->dir)) {
            $dir = 'desc';
        } else {
            $dir = $this->context->dir;
        }

        return $this->context->sort . ' ' . $dir;
    }

    private function formatList($list): array
    {
        $format = [];

        if (empty($list)) {
            return $format;
        }

        foreach ($list as &$item)
        {
            foreach ($item as &$value)
            {
                if (!is_array($value) && !is_object($value)) {
                    $value = strval($value);
                }
            }

            // 查询用户信息
            $info = $this->staffService->getInfoByUid($item['kf_id']);
            $item['id'] = $item['kf_id'];
            $item['user_name'] = $info['user_name'] ?? '';

            // 查询部门名称
            $orgInfo = $this->orgModel::getInfoById($item['org_id']);
            $item['org_name'] = $orgInfo['org_name'] ?? '未分配';

            $item['right_pic_url_show'] = Helper::getHeadUrl($item['right_pic_url']);
        }

        return $list;
    }
}