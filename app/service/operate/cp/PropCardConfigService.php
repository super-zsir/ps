<?php

namespace Imee\Service\Operate\Cp;

use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class PropCardConfigService
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
        $type = intval(array_get($params, 'type', 0));

        $query = [];
        $id && $query[] = ['id', '=', $id];
        $type && $query[] = ['type', '=', $type];

        $data = XsPropCardConfig::getListAndTotal($query, '*', 'id desc', $page, $limit);

        $logs = BmsOperateLog::getFirstLogListByAction('propcardconfig', array_pluck($data['data'], 'id'), 0);

        foreach ($data['data'] as &$rec) {
            $dateline = array_get($rec, 'dateline', 0);
            $log = array_get($logs, $rec['id'], []);


            $_nameJson = @json_decode(array_get($rec, 'name_json', ''), true);
            $_descriptionJson = @json_decode(array_get($rec, 'description_json', ''), true);

            foreach ($_nameJson as $k => $v) {
                $rec['name_' . $k] = $v;
            }
            foreach ($_descriptionJson as $k => $v) {
                $rec['description_' . $k] = $v;
            }

            $rec['operate_name'] = array_get($log, 'operate_name', '');
            $rec['created_time'] = array_get($log, 'created_time', '');
            $rec['created_time'] = $rec['created_time'] ? date('Y-m-d H:i', $rec['created_time']) : '';
            $rec['cover_img_url'] = Helper::getHeadUrl(array_get($rec, 'cover_img', ''));
            $rec['icon_url'] = Helper::getHeadUrl(array_get($rec, 'icon', ''));
            $rec['dateline'] = $dateline ? date('Y-m-d H:i', $dateline) : '';
        }

        return $data;
    }

    public function add($params): array
    {
        $data = $this->validateAndFormatData($params);
        list($flg, $rec) = $this->rpc->propCardConfigAdd($data);
        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data, ['id' => $rec])] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $setting = XsPropCardConfig::findOne($id);
        if (empty($setting)) {
            return [false, '数据不存在'];
        }
        $data = $this->validateAndFormatData($params);

        list($flg, $rec) = $this->rpc->propCardConfigEdit($data);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $data)] : $rec];
    }

    public function delete($params): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsPropCardConfig::findOne($id);
        if (empty($setting)) {
            return [false, '数据不存在'];
        }
        return [false, '删除失败'];
    }

    private function validateAndFormatData($params): array
    {
        $id = (int)array_get($params, 'id', 0);

        $nameJson = [];
        $descriptionJson = [];
        $area = ['cn', 'en', 'ar', 'tr', 'ur', 'hi', 'bn', 'tl', 'vi', 'id', 'ms', 'ko', 'th'];
        foreach ($area as $v) {
            $nameJson[$v] = array_get($params, 'name_' . $v, '');
            $descriptionJson[$v] = array_get($params, 'description_' . $v, '');
        }
        $nameJson = @json_encode($nameJson);
        $descriptionJson = @json_encode($descriptionJson);
        $data = [
            'type' => (int)array_get($params, 'type', 0),
            'name_json' => $nameJson,
            'description_json' => $descriptionJson,
            'cover_img' => array_get($params, 'cover_img', ''),
            'icon' => array_get($params, 'icon', ''),
        ];

        $id && $data['id'] = $id;
        return $data;
    }

    public function getTypeMaps($value = null, $format = '')
    {
        $map = XsPropCardConfig::$typeMaps;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    public function getTypeAllMaps($value = null, $format = '')
    {
        $map = XsPropCardConfig::$typeAllMaps;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

}