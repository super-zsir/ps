<?php

namespace Imee\Service\Luckywheel;

use Imee\Comp\Common\Log\Service\OperateLog;
use Imee\Models\Xs\XsLuckyWheelConfig;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;

class LuckyWheelConfigService
{
    /**
     * @var string 配置类型
     */
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function getList()
    {
        $config = XsLuckyWheelConfig::findOneByWhere([]);
        if ($this->type == 'gain') {
            return $this->onFormatList($config);
        }
        $list = [];
        $data = !empty($config[$this->type]) ? @json_decode($config[$this->type],true) : [];
        foreach ($data as $k => $v) {
            $list[XsLuckyWheelConfig::PARAM_PREFIX . ($k + 1)] = $v;
        };
        return $this->onFormatList($list);;
    }

    private function onFormatList($data)
    {
        $log = $this->getOperateFirstLog('xs_lucky_wheel_config', XsLuckyWheelConfig::$type[$this->type]);
        return array_merge($data, $log);
    }

    public function getOperateFirstLog($model, $content): array
    {
        if (!$model || !$content) {
            return [];
        }

        $condition = [];
        $condition[] = ['model', '=', $model];
        $condition[] = ['content', '=', $content];
        $data = BmsOperateLog::getListByWhere($condition, 'model_id,operate_name,created_time', 'id desc', 1);

        if (!$data) {
            return [];
        }

        $data[0]['created_time'] = isset($data[0]['created_time']) ? Helper::now($data[0]['created_time']) : '';

        return $data[0];
    }

    public function modify(array $params): array
    {
        $config = XsLuckyWheelConfig::findOneByWhere([]);
        if ($this->type == XsLuckyWheelConfig::TICKETS_PRICE_FIELD || $this->type == XsLuckyWheelConfig::JOIN_NUMS_FIELD) {
            $data = $this->formatConfigData($params);
            if (count($data[$this->type]) < 2 || count($data[$this->type]) > 6) {
                return [false, '配置项最少配置2个，最多配置6个'];
            }
        } else if($this->type == 'gain') {
            $data = $this->formatGainData($params);
        }
        $insert = [
            'join_nums_gear'     => json_decode($config['join_nums_gear']),
            'tickets_price_gear' => json_decode($config['tickets_price_gear']),
            'homeowner_gain'     => (int) $config['homeowner_gain'],
            'platform_gain'      => (int) $config['platform_gain'],
            'winner_gain'        => (int) $config['winner_gain']
        ];
        list($res, $msg) = (new PsService())->modifyLuckyWheel(array_merge($insert, $data));
        if (!$res) {
            return [false, $msg];
        }
        $this->addLog($data, $insert);
        return [true, ''];
    }

    private function addLog($data, $config)
    {
        $type = XsLuckyWheelConfig::$type;
        $logs = [
            'model'       => 'xs_lucky_wheel_config',
            'model_id'    => $config['id'] ?? 0,
            'action'      => BmsOperateLog::ACTION_UPDATE,
            'content'     => $type[$this->type],
            'before_json' => $config,
            'after_json'  => $data,
            'operate_id'  => Helper::getSystemUid()
        ];
        OperateLog::addOperateLog($logs);
    }

    private function formatConfigData(array $params): array
    {
        $config = [];
        foreach ($params as $k => $v) {
            if (stristr($k, XsLuckyWheelConfig::PARAM_PREFIX) && !empty($v)) {
                $config[] = (int) $v;
            }
        }
        return [
            $this->type => $config
        ];
    }

    private function formatGainData(array $params): array
    {
        return [
            'homeowner_gain' => (int) $params['homeowner_gain'],
            'platform_gain'  => (int) $params['platform_gain'],
            'winner_gain'    => (int) (100 - $params['homeowner_gain'] - $params['platform_gain'])
        ];
    }

    public function validation(&$params): array
    {
        $params['homeowner_gain'] = (int) ($params['homeowner_gain'] ?? 0);
        $params['platform_gain']  = (int) ($params['platform_gain'] ?? 0);
        if ($params['homeowner_gain'] < 0 || $params['homeowner_gain'] > 100) {
            return [false, '房主分成错误，最小值0，最大值为100'];
        }

        if ($params['platform_gain'] < 0 || $params['platform_gain'] > 100) {
            return [false, '平台分成错误，最小值0，最大值为100'];
        }

        if ($params['platform_gain'] + $params['homeowner_gain'] > 100) {
            return [false, '分成值相加必须等于100'];
        }

        return [true, ''];
    }
}