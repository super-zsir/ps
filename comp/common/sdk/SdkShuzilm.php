<?php

namespace Imee\Comp\Common\Sdk;

use Phalcon\Di;

class SdkShuzilm extends SdkBase
{
    private $format = 'json';
    private $_platform = '';
    private $_protocol = 2;
    private $_ver = '1.4.0';

    public function __construct($format = self::FORMAT_JSON, $timeout = 10, $waning = 0.03)
    {
        parent::__construct($format, $timeout, $waning);
    }

    public function setPlatform($platform)
    {
        $this->_platform = $platform;
    }

    private function getApiUrl()
    {
        if ($this->_platform == 'ios') {
            $url = 'https://iddi.shuzilm.cn/q';
        } else if ($this->_platform == 'android') {
            $url = 'https://ddi.shuzilm.cn/q';
        } else {
            $url = '';
        }
        return $url;
    }

    public function query($did, $pkg)
    {
        $url = $this->getApiUrl();
        if (empty($url)) return false;
        $params = [
            'protocol' => $this->_protocol,
            'did'      => $did,
            'pkg'      => $pkg,
        ];
        $api = $url . '?' . http_build_query($params);
        $response = $this->request($api, false, null, null, SdkBase::FORMAT_JSON);
        if (!$response) {
            return false;
        }
        Di::getDefault()->getShared('logger')->error("[SdkShuZilm] get response: " . @json_encode($response));
        $err = isset($response['err']) ? intval($response['err']) : -1;
        if ($err === 0) {
            return [
                'device_type'     => $response['device_type'],
                'normal_times'    => $response['normal_times'],
                'duplicate_times' => $response['duplicate_times'],
                'update_times'    => $response['update_times'],
                'recall_times'    => $response['recall_times'],
            ];
        }
        return false;
    }

    public function isEmulator($did, $pkg)
    {
        $res = $this->query($did, $pkg);
        if (!$res) {
            return false;
        }
        if ($res['device_type'] == 1) {
            return true;
        }
        return false;
    }
}
