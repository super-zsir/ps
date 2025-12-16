<?php

namespace Imee\Service\Domain\Service\Cs;

use Imee\Comp\Common\Sdk\SdkBanBanIm;
use Imee\Helper\Traits\SingletonTrait;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserBigarea;
use Imee\Models\Xs\XsUserSettings;

class WorkorderService
{
    use SingletonTrait;

    /**
     * 批量获取用户的信息
     * @param array $uids
     * @return array
     */
    public function userInfo(array $data)
    {
        $uids = $data['uids'] ?? [];
        if (empty($uids)) {
            return ['msg' => '当前无用户'];
        }
        // 用户语言
        $setting = XsUserSettings::getListByWhere([
            ['uid', 'in', $uids]
        ]);

        $newSetting = [];
        if ($setting) {
            $newSetting = array_column($setting, null, 'uid');
        }
        // 用户大区
        $userBigarea = XsUserBigarea::getListByWhere([
            ['uid', 'in', $uids]
        ]);
        $userBigarea = array_column($userBigarea, 'bigarea_id', 'uid');
        $bigIds = array_values($userBigarea);
        $xsBigarea = [];
        if ($bigIds) {
            $xsBigarea = XsBigarea::getListByWhere([
                ['id', 'in', $bigIds]
            ]);
            $xsBigarea = array_column($xsBigarea, 'name', 'id');
        }
        $userBigarea = array_map(function($i) use ($xsBigarea){
            return $xsBigarea[$i] ?? '';
        }, $userBigarea);
        $res = [];
        foreach ($uids as $uid) {
            $res[$uid] = [
                'language' => $newSetting[$uid]['language'] ?? '',
                'area' => $userBigarea[$uid] ?? '',
            ];
        }
        return $res;
    }

    /**
     * 下发抓取通知
     * @param array $data
     * @return array
     */
    public function appDebug(array $data): array
    {
        $uid = $data['uid'] ?? 0;
        $id = $data['id'] ?? 0;
        try {
            $res = (new SdkBanBanIm())->cmd($uid, 'upload.log', ['id' => intval($id), 'uid' => intval($uid)]);
        } catch (\Exception $e) {
            return ['msg' => $e->getMessage()];
        }
        if (!isset($res['code']) || $res['code'] != 200) {
            return ['msg' => json_encode($res)];
        }
        return [];
    }
}