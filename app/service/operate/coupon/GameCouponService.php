<?php

namespace Imee\Service\Operate\Coupon;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserCouponLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class GameCouponService
{
    /** @var PsService $psServer */
    private $psServer;

    public function __construct()
    {
        $this->psServer = new PsService();
    }

    public function getListAndTotal($params): array
    {

        $id = (int)array_get($params, 'id', 0);
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);

        $filter = ['page_size' => $limit, 'page_num' => $page];
        $id && $filter['id'] = $id;

        $data = $this->psServer->getGameCouponList($filter);

        $bigAreaList = array_keys(XsBigarea::$_bigAreaMap);

        foreach ($data['data'] as &$rec) {
            $scenes = array_column($rec['scenes'], 'name', 'id');

            $rec['scene'] = array_map(function ($scene) {
                return (string)$scene;
            }, array_keys($scenes));
            $rec['scene_name'] = implode(',', $scenes);
            $rec['icon_img'] = Helper::getHeadUrl(array_get($rec, 'icon', ''));

            foreach ($bigAreaList as $v) {
                $_name = array_get($rec['name'], $v);
                $_desc = array_get($rec['desc'], $v);
                $_name && $rec['name_' . $v] = array_get($rec['name'], $v, '');
                $_desc && $rec['desc_' . $v] = array_get($rec['desc'], $v, '');
            }
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);

        list($flg, $rec) = $this->psServer->gameCouponCreate($data);

        return [$flg, $flg ? ['after_json' => $data] : $rec];
    }

    public function modify($params): array
    {
        $data = $this->validateAndFormatData($params);

        list($flg, $rec) = $this->psServer->gameCouponModify($data);

        $beforeJson = [];
        if ($flg) {
            $couponList = $this->psServer->getGameCouponList(['id' => $data['id']]);
            if (isset($couponList['data']) && is_array($couponList['data']) && count($couponList['data'])) {
                $beforeJson = $couponList['data'][0];
            }
        }


        return [$flg, $flg ? ['before_json' => $beforeJson, 'after_json' => $data] : $rec];
    }

    public function getUseLogListAndTotal($params): array
    {
        $page = (int)array_get($params, 'page', 1);
        $limit = (int)array_get($params, 'limit', 15);
        $bigareaId = intval(array_get($params, 'bigarea_id', 0));
        $uid = intval(array_get($params, 'uid', 0));

        $startTime = trim(array_get($params, 'start_time', ''));
        $endTime = trim(array_get($params, 'end_time', ''));
        $startTime = $startTime ? strtotime($startTime) : (time() - 7 * 24 * 60 * 60);
        $endTime = $endTime ? strtotime($endTime) : time();

        $filter = [['coupon_type', '=', XsUserCouponLog::COUPON_TYPE]];
        $bigareaId && $filter[] = ['bigarea_id', '=', $bigareaId];

        $uid && $filter[] = ['uid', '=', $uid];
        $startTime && $filter[] = ['dateline', '>=', $startTime];
        $endTime && $filter[] = ['dateline', '<=', $endTime];
        if (empty($uid) && empty($bigareaId)) {
            return ['data' => [], 'total' => 0];
        }

        $data = XsUserCouponLog::getListAndTotal($filter, '*', 'dateline desc', $page, $limit);

        $amountList = $this->psServer->getGameCouponAllList();
        $amountList = array_column($amountList, 'amount', 'id');


        foreach ($data['data'] as &$rec) {
            $couponId = array_get($rec, 'coupon_id', 0);
            $amount = array_get($amountList, $couponId, 0);

            $reason = array_get($rec, 'reason', '');
            $reason = $reason ? json_decode($reason, true) : [];
            $rec['scene'] = array_get($reason, 'scene', '');
            $rec['game_id'] = array_get($reason, 'id', '');
            $rec['dateline'] = $rec['dateline'] ? date('Y-m-d H:i:s', $rec['dateline']) : '';
            $rec['amount'] = $amount;
            $rec['price'] = $amount * (int)$rec['num'];
        }

        return $data;
    }

    public static function getGameCouponAmounts($value = null, string $format = '')
    {
        $data = (new PsService())->getGameCouponAmount();
        $map = [];
        foreach ($data as $v) {
            $map[$v] = $v;
        }
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getGameCouponType($value = null, string $format = '')
    {
        $map = (new PsService())->getGameCouponScene();
        foreach ($map as $k => $v) {
            $map[$k] = $v;
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getGameCouponList($value = null, string $format = '')
    {
        $data = (new PsService())->getGameCouponAllList();

        $map = [];
        foreach ($data as $rec) {
            $map[$rec['id']] = 'id: ' . $rec['id'] . ' (' . $rec['amount'] . '💎)';
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    public static function getGameCouponLogOp($value = null, string $format = '')
    {
        $map = XsUserCouponLog::$op;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }


    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $icon = array_get($params, 'icon', '');
        $amount = array_get($params, 'amount');
        $scene = array_get($params, 'scene', []);

        if (empty($icon)) {
            throw new ApiException(ApiException::MSG_ERROR, '请上传预览图');
        }
        if (empty($amount)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择档位');
        }
        if (empty($scene)) {
            throw new ApiException(ApiException::MSG_ERROR, '请选择游戏类型');
        }

        $nameJson = [];
        $descJson = [];
        $bigAreaList = array_keys(XsBigarea::$_bigAreaMap);

        foreach ($bigAreaList as $v) {
            $_name = array_get($params, 'name_' . $v);
            $_desc = array_get($params, 'desc_' . $v);

            if ($_name) {
                $nameJson[$v] = $_name;
            }

            if ($_desc) {
                $descJson[$v] = $_desc;
            }
        }

        $sceneArr = [];
        foreach ($scene as $v) {
            $sceneArr[] = ['id' => intval($v)];
        }

        $data = [
            'icon' => $icon,
            'name' => $nameJson,
            'desc' => $descJson
        ];
        if ($id) {
            $data['id'] = $id;
        } else {
            $data['amount'] = intval($amount);
            $data['scenes'] = $sceneArr;
        }
        return $data;
    }


}