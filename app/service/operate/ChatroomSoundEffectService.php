<?php

namespace Imee\Service\Operate;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsChatroomSoundEffect;
use Imee\Models\Xs\XsChatroomSoundEffectBigArea;
use Imee\Models\Xs\XsInteractiveEmoticonsListed;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class ChatroomSoundEffectService
{
    /** @var PsService $rpc */
    private $rpc;

    public function __construct()
    {
        $this->rpc = new PsService();
    }

    public function getListAndTotal(array $params): array
    {
        $limit = array_get($params, 'limit', 15);
        $page = array_get($params, 'page', 1);

        $id = intval(array_get($params, 'id', 0));
        $name = trim(array_get($params, 'name', ''));

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $name && $query[] = ['name_json', 'like', $name];

        $data = XsChatroomSoundEffect::getListAndTotal($query, '*', 'id desc', $page, $limit);

        foreach ($data['data'] as &$rec) {
            $_id = array_get($rec, 'id', 0);
            $_createTime = array_get($rec, 'create_time', 0);
            $_updateTime = array_get($rec, 'update_time', 0);
            $rec['create_time'] = $_createTime ? date('Y-m-d H:i:s', $_createTime) : '';
            $rec['update_time'] = $_updateTime ? date('Y-m-d H:i:s', $_updateTime) : '';

            $rec['name_show'] = $this->getNameShow($rec['name_json']);
            $rec['sound_show'] = sprintf("%s <br/> %s", basename($rec['sound_url']), Helper::formatDurationExtended($rec['duration_seconds']));

            $_area = $this->getBigAreaName($_id);
            $rec['big_area_id'] = array_keys($_area);
            $rec['area_show'] = implode('<br/>', array_values($_area));

            $rec['icon_url_show'] = Helper::getHeadUrl($rec['icon_url'] ?? '');
            $rec['sound_url_show'] = Helper::getHeadUrl($rec['sound_url'] ?? '');
            $rec['effect_url_show'] = Helper::getHeadUrl($rec['effect_url'] ?? '');
            $rec['name_json'] = @json_decode($rec['name_json'], true);
        }
        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = $this->rpc->soundEffectCreate($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsChatroomSoundEffect::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = $this->rpc->soundEffectEdit($data);;

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $data)] : $rec];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $adminId = array_get($params, 'admin_id', 0);
        $setting = XsChatroomSoundEffect::findOne($id);
        if (empty($setting)) {
            return [false, 'ID数据错误'];
        }

        list($flg, $rec) = $this->rpc->soundEffectDel([
            'id'       => $id,
            'operator' => Helper::getAdminName($adminId),
        ]);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => []] : $rec];
    }

    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $adminId = array_get($params, 'admin_id', 0);
        $nameJson = array_get($params, 'name_json', []);
        $iconUrl = trim(array_get($params, 'icon_url', ''));
        $soundUrl = trim(array_get($params, 'sound_url', ''));
        $durationSeconds = 0;
        $effectUrl = trim(array_get($params, 'effect_url', ''));
        $status = (int)array_get($params, 'status', 2);
        $bigAreaId = array_get($params, 'big_area_id', []);
        $operator = Helper::getAdminName($adminId);

        $bigAreaId = Helper::formatIds($bigAreaId);

        //通过路径保存文件播放时长
        if (preg_match('/sound\/([^\/]+)\//', $soundUrl, $matches)) {
            $pathValue = explode('_', $matches[1] ?? '');
            if (isset($pathValue[1]) && is_numeric($pathValue[1])) {
                $durationSeconds = intval($pathValue[1]);
            }
        }

        $data = [
            'name_json'        => @json_encode($nameJson, JSON_UNESCAPED_UNICODE),
            'icon_url'         => $iconUrl,
            'sound_url'        => $soundUrl,
            'duration_seconds' => $durationSeconds,
            'effect_url'       => $effectUrl,
            'big_area_id'      => $bigAreaId,
            'status'           => $status,
            'operator'         => $operator,
        ];
        $id && $data['id'] = $id;

        return $data;
    }

    public function getOptions(): array
    {
        $lan = XsBigarea::getLanguageArr();
        return [
            'big_area_id' => StatusService::formatMap(XsBigarea::getAllNewBigArea(), StatusService::PARAMS_FORMAT),
            'lan'         => StatusService::formatMap($lan, StatusService::PARAMS_FORMAT),
        ];
    }

    public function getInfo($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        if (empty($id)) {
            return [];
        }
        $model = XsChatroomSoundEffect::findOne($id);

        if (!empty($model)) {
            $model['big_area_id'] = $this->getBigAreaName($id, false);
            $model['name_json'] = @json_decode($model['name_json'], true);;
            $model['icon_url_show'] = Helper::getHeadUrl($model['icon_url'] ?? '');
            $model['sound_url_show'] = Helper::getHeadUrl($model['sound_url'] ?? '');
            $model['effect_url_show'] = Helper::getHeadUrl($model['effect_url'] ?? '');
        }
        return $model;
    }

    public static function getStatusMap($value = null, string $format = '')
    {
        $map = XsChatroomSoundEffect::$statusMap;
        if (!empty($value)) {
            return $map[$value] ?? '';
        }
        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }
        return $map;
    }

    private function getNameShow($nameJson): string
    {
        $nameArr = @json_decode($nameJson, true);
        $str = [];
        $lan = XsBigarea::getLanguageArr();
        foreach ($nameArr as $key => $val) {
            $str[] = sprintf('%s（%s）: %s', isset($lan[$key]) ? $lan[$key] : $key, $key, $val);
        }
        return implode('<br/>', $str);
    }

    private function getBigAreaName($id, $isName = true): array
    {
        $area = [];
        $allBigArea = XsBigarea::getAllNewBigArea();
        $lists = XsChatroomSoundEffectBigArea::getListByWhere([
            ['sound_effect_id', '=', $id],
//            ['status', '=', XsChatroomSoundEffectBigArea::STATUS_NORMAL]
        ]);
        $arr = array_map(function ($v) {
            return (string)$v;
        }, array_column($lists, 'big_area_id'));

        if ($isName) {
            foreach ($arr as $v) {
                $area[$v] = $allBigArea[$v] ?? '';
            }
        } else {
            $area = $arr;
        }

        return $area;
    }

}