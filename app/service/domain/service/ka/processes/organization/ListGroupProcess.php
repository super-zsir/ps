<?php

namespace Imee\Service\Domain\Service\Ka\Processes\Organization;


use Imee\Models\Xsst\Ka\XsstKaOrganization;
use Imee\Models\Xsst\Ka\XsstKaOrganizationUser;
use Imee\Service\Domain\Context\Ka\Organization\ListGroupContext;

class ListGroupProcess
{
    /**
     * @var ListGroupContext
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

    public function __construct(ListGroupContext $context)
    {
        $this->context = $context;
    }

    public function handle(): array
    {
        $first = [['id' => 0, 'pid' => 0, 'org_name' => '未分配', 'path' => '0', 'weight' => 999]];
        $plist = $this->orgModel::findParentList();

        if (empty($plist)) {
            return $first;
        }

        $plist = array_column($plist, null, 'id');

        $clist = $this->orgModel::query()
            ->where('pid > :pid:', ['pid' => 0])
            ->execute()
            ->toArray();

        if (!empty($clist)) {
            $clist = array_column($clist, null, 'id');
        }

        foreach ($clist as $id => $item)
        {
            $pathArr = explode(',', $item['path']);

//            if (count($pathArr) > 1) {
//                unset($pathArr[count($pathArr) - 1]);
//            }

            // 最多限制5级
            $tmpId1 = $pathArr[0] ?? 0;
            $tmpId2 = $pathArr[1] ?? 0;
            if ($tmpId2 > 0
                && isset($clist[$tmpId2])
                && !isset($plist[$tmpId1]['children'][$tmpId2])
            ) {
                $plist[$tmpId1]['children'][$tmpId2] = $clist[$tmpId2];
            }

            $tmpId3 = $pathArr[2] ?? 0;
            if ($tmpId3 > 0
                && isset($clist[$tmpId3])
                && !isset($plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3])
            ) {
                $plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3] = $clist[$tmpId3];
            }

            $tmpId4 = $pathArr[3] ?? 0;
            if ($tmpId4 > 0
                && isset($clist[$tmpId4])
                && !isset($plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3]['children'][$tmpId4])
            ) {
                $plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3]['children'][$tmpId4] = $clist[$tmpId4];
            }

            $tmpId5 = $pathArr[4] ?? 0;
            if ($tmpId5 > 0
                && isset($clist[$tmpId5])
                && !isset($plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3]['children'][$tmpId4]['children'][$tmpId5])
            ) {
                $plist[$tmpId1]['children'][$tmpId2]['children'][$tmpId3]['children'][$tmpId4]['children'][$tmpId5] = $clist[$tmpId5];
            }

        }

        $plist = array_merge($first, $this->resetKey($plist));

        return array_values($plist);
    }

    private function resetKey($list)
    {
        foreach ($list as &$item)
        {
            if (isset($item['children']) && !empty($item['children'])) {
                $item['children'] = array_values($item['children']);
                $item['children'] = $this->resetKey($item['children']);
            }
        }

        return array_values($list);
    }
}