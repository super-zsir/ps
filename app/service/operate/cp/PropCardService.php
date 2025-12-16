<?php

namespace Imee\Service\Operate\Cp;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;

class PropCardService
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
        $bigareaIdArr = array_get($params, 'bigarea_id', []);
        $deleted = array_get($params, 'deleted');

        $query = [];
        $id && $query[] = ['id', '=', $id];
        !empty($bigareaIdArr) && $query[] = ['bigarea_id', 'IN', $bigareaIdArr];
        is_numeric($deleted) && $query[] = ['deleted', '=', $deleted];

        $data = XsPropCard::getListAndTotal($query, '*', 'id desc', $page, $limit);
        $configList = XsPropCardConfig::getBatchCommon(Helper::arrayFilter($data['data'], 'prop_card_config_id'), ['id', 'type']);
        $logs = BmsOperateLog::getFirstLogListByAction('propcard', array_pluck($data['data'], 'id'), 0);

        foreach ($data['data'] as &$rec) {
            $extend = @json_decode($rec['extend'], true);
            $rec['hours'] = $extend['hours'] ?? '';
            $rec['relation_type'] = strval($extend['relation_type'] ?? '');
            $rec['buy_use_level'] = strval($extend['buy_use_level'] ?? '');
            $dateline = array_get($rec, 'dateline', 0);
            $log = array_get($logs, $rec['id'], []);
            $config = array_get($configList, $rec['prop_card_config_id'], []);
            $rec['type'] = array_get($config, 'type', 0);
            $rec['operate_name'] = array_get($log, 'operate_name', '');
            $rec['created_time'] = array_get($log, 'created_time', '');
            $rec['created_time'] = $rec['created_time'] ? date('Y-m-d H:i', $rec['created_time']) : '';
            $rec['dateline'] = $dateline ? date('Y-m-d H:i', $dateline) : '';
            $rec['note'] = self::getNoteShow($rec);
        }

        return $data;
    }

    public function add($params): array
    {
        $propCardConfigId = (int)array_get($params, 'prop_card_config_id', 0);
        $bigareaIdArr = array_get($params, 'bigarea_id', []);
        $type = array_get($params, 'type', 0);
        $relationType = array_get($params, 'relation_type', 0);
        $buyUseLevel = array_get($params, 'buy_use_level', 0);


        switch ($type) {
            case XsPropCardConfig::TYPE_RELIEVE_CARD:
            case XsPropCardConfig::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON:
            case XsPropCardConfig::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME:
                $config = array_get($params, 'config', []);
                if (empty($config)) {
                    throw new ApiException(ApiException::MSG_ERROR, '配置不能为空');
                }
                foreach ($config as &$v) {
                    $validityValue = (int)array_get($v, 'validity_value', 0);
                    $price = (int)array_get($v, 'price', 0);
                    if ($validityValue < -1 || $price < 0) {
                        throw new ApiException(ApiException::MSG_ERROR, '价格或有效期输入格式错误');
                    }
                    $v['validity_value'] = $validityValue;
                    $v['price'] = $price;

                    if (in_array($type, [XsPropCardConfig::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON, XsPropCardConfig::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME])) {
                        $v['extend'] = json_encode(['relation_type' => (int) $relationType, 'buy_use_level' => (int) $buyUseLevel]);
                    }
                }
                break;

            case XsPropCardConfig::TYPE_PK_PROP_CARD_ADD:
                $ratio = (int)array_get($params, 'ratio', 0);
                if ($ratio < 0) {
                    throw new ApiException(ApiException::MSG_ERROR, '加成值为大于0的正整数');
                }
                $config = [['extend' => json_encode(['ratio' => $ratio])]];
                $bigareaIdArr = [0];
                break;

            case XsPropCardConfig::TYPE_PK_PROP_CARD_MAG:
                $ratio = (int)array_get($params, 'ratio', 0);
                if ($ratio < 0 || $ratio >= 100) {
                    throw new ApiException(ApiException::MSG_ERROR, '磁力值为大于0小于100的正整数');
                }
                $config = [['extend' => json_encode(['ratio' => $ratio])]];
                $bigareaIdArr = [0];
                break;

            default:
                $hours = (int)array_get($params, 'hours', 0);
                if ($hours < 0) {
                    throw new ApiException(ApiException::MSG_ERROR, '解封时间输入格式错误');
                }
                $propCardHours = XsPropCard::getPropCardHoursByPropCardId($propCardConfigId);
                if ($propCardHours && $hours != $propCardHours) {
                    throw new ApiException(ApiException::MSG_ERROR, '同一个解封卡物品id不能配置不同的解封时间');
                }
                $config = [['extend' => json_encode(['hours' => $hours])]];
                $bigareaIdArr = [0];
                break;

        }


        $data = [
            'prop_card_config_id'  => $propCardConfigId,
            'big_area_ids'         => $bigareaIdArr,
            'prop_card_price_list' => $config,
        ];
        list($flg, $rec) = $this->rpc->propCardAdd($data);

        return [$flg, $flg ? ['id' => $rec, 'after_json' => array_merge($data)] : $rec];
    }

    public function modify($params): array
    {
        $id = (int)array_get($params, 'id', 0);
        $setting = XsPropCard::findOne($id);
        if (empty($setting)) {
            return [false, '数据不存在'];
        }

        $config = XsPropCardConfig::findOne($setting['prop_card_config_id']);

        if (empty($config) || $config['type'] != XsPropCardConfig::TYPE_RELIEVE_CARD) {
            throw new ApiException(ApiException::MSG_ERROR, '只有解除卡才能修改价格和有效期');
        }

        if (array_get($setting, 'deleted') == XsPropCard::DELETED_NO) {
            return [false, '只有下架的时候才能修改价格和有效期'];
        }

        $validityValue = (int)array_get($params, 'validity_value', 0);
        $price = (int)array_get($params, 'price', 0);

        $data = [
            'id'             => $id,
            'validity_value' => $validityValue,
            'price'          => $price,
            'deleted'        => (int)array_get($setting, 'deleted', 0),
        ];

        list($flg, $rec) = $this->rpc->propCardEdit($data);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $data)] : $rec];
    }

    public function delete($params, int $deleted = XsPropCard::DELETED_YES): array
    {
        $id = (int)array_get($params, 'id');
        $setting = XsPropCard::findOne($id);
        if (empty($setting) || array_get($setting, 'deleted') == $deleted) {
            return [false, '数据不存在'];
        }
        $config = XsPropCardConfig::findOne($setting['prop_card_config_id']);

        $data = [
            'id'      => $id,
            'deleted' => $deleted,
        ];

        if ($config['type'] == XsPropCardConfig::TYPE_RELIEVE_CARD) {
            $data['validity_value'] = (int)array_get($setting, 'validity_value', 0);
            $data['price'] = (int)array_get($setting, 'price', 0);
        } else {
            $extend = @json_decode($setting['extend'], true);
            $data['extend'] = ['hours' => (int)array_get($extend, 'hours', 0)];
        }

        list($flg, $rec) = $this->rpc->propCardEdit($data);

        return [$flg, $flg ? ['before_json' => $setting, 'after_json' => array_merge($setting, $data)] : $rec];
    }

    public function getPropCardConfigMaps($value = null, $format = '')
    {
        $map = XsPropCardConfig::getPropCardConfigMaps();
        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    public function getDeletedMaps($value = null, $format = '')
    {
        $map = XsPropCard::$deletedMaps;

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    public function getBigAreaMaps($value = null, $format = '')
    {
        $map = [];
        //        中文区，英文区，阿语区，土耳其语区，巴基斯坦区，印度区，孟加拉区，菲律宾区，越南区，印尼区，马来区，韩区
        $area = ['cn', 'en', 'ar', 'tr', 'ur', 'hi', 'bn', 'tl', 'vi', 'id', 'ms', 'ko',];
        $lists = XsBigarea::getListByWhere([['name', 'IN', $area]], 'id, name');
        foreach ($lists as $v) {
            $map[$v['id']] = XsBigarea::getBigAreaCnName($v['name']);
        }

        if (!empty($value)) {
            return $map[$value] ?? '';
        }

        if (!empty($format)) {
            $map = StatusService::formatMap($map, $format);
        }

        return $map;
    }

    public function getOptions(): array
    {
        $bigAreaMap = $this->getBigAreaMaps(null, 'label,value');
        $propMap = $this->getPropCardConfigMaps(null, 'label,value');
        $relationTypeMap = $this->getRelationTypeMaps(null, 'label,value');
        $buyUseLevelMap = $this->getBuyUseLevelMaps(null, 'label,value');
        return compact('bigAreaMap', 'propMap', 'relationTypeMap', 'buyUseLevelMap');
    }

    public function getType(array $params): array
    {
        $id = intval($params['prop_card_config_id'] ?? 0);

        $card = XsPropCardConfig::findOne($id);

        return [
            'type' => strval($card['type'] ?? 0),
        ];
    }

    public static function getNoteShow($data): string
    {
        $str = '';
        $extend = $data['extend'] ? @json_decode($data['extend'], true) : [];

        switch ($data['type']) {
            case XsPropCardConfig::TYPE_PK_PROP_CARD_ADD:
                $str = sprintf("加成值：%s", $extend['ratio'] ? $extend['ratio'] . '%' : '');
                break;
            case XsPropCardConfig::TYPE_PK_PROP_CARD_MAG:
                $str = sprintf('磁力值：%s', $extend['ratio'] ? $extend['ratio'] . '%' : '');
                break;
            case XsPropCardConfig::TYPE_PK_PROP_CARD_INTIMATE_RELATION_ICON:
            case XsPropCardConfig::TYPE_PK_PROP_CARD_RELATION_AVATAR_FRAME:
                $relationType = XsPropCardConfig::$relationTypeMaps[$extend['relation_type'] ?? ''] ?? '';
                $buyUseLevel = XsPropCardConfig::$buyUseLevelMaps[$extend['buy_use_level'] ?? ''] ?? '';
                $str = sprintf('关系类型：%s，可购买和可使用的关系等级：%s', $relationType, $buyUseLevel);
                break;
            default:
                break;
        }
        return $str;
    }

    /**
     * 获取关联类型
     * @param $value
     * @param $format
     * @return array
     */
    public function getRelationTypeMaps($value = null, $format = ''): array
    {
        return StatusService::formatMap(XsPropCardConfig::$relationTypeMaps);
    }

    /**
     * 获取可购买和可用的关系等级
     * @param $value
     * @param $format
     * @return array
     */
    public function getBuyUseLevelMaps($value = null, $format = ''): array
    {
        return StatusService::formatMap(XsPropCardConfig::$buyUseLevelMaps);
    }
}