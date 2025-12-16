<?php

namespace Imee\Service\Domain\Service\Audit\Processes\RiskCheck\ForbiddenCheck;

use Imee\Service\Domain\Context\Audit\RiskCheck\ForbiddenCheck\DurationContext;
use Imee\Models\Xs\XsUserForbiddenDuration;
use Imee\Models\Xs\XsUserVersion;
use Imee\Models\Xs\XsUserForbidden;
use Imee\Models\Xs\XsMac;
use Imee\Models\Xs\XsUserSettings;

class DurationProcess
{
    private $context;

    public function __construct(DurationContext $context)
    {
        $this->context = $context;
    }

    private function getMac()
    {
        $mac         = '';
        $versionData = XsUserVersion::findFirst([
            'conditions' => 'uid=:uid:',
            'bind'       => [
                'uid' => $this->context->uid,
            ]
        ]);
        if ($versionData) {
            $mac = $versionData->mac;
        }
        return $mac;
    }

    private function getImeiAndMacNeed($mac)
    {
        $imei         = '';
        $macNeed      = 0;
        $macNeedData  = null;
        $macNeedData2 = null;

        $macConditions = 'uid=:uid:';
        $macBind       = [
            'uid' => $this->context->uid,
        ];
        if ($mac) {
            $macNeedData    = XsUserForbidden::findFirst([
                'conditions' => 'mac=:mac:',
                'bind'       => [
                    'mac' => $mac,
                ]
            ]);
            $macConditions  = 'uid=:uid:' . ' or mac=:mac:';
            $macBind['mac'] = $mac;
        }
        $macData = XsMac::find([
            'conditions' => $macConditions,
            'bind'       => $macBind,
            'order'      => 'id desc',
            'offset'     => 0,
            'limit'      => 10,
        ])->toArray();

        if ($macData) {
            foreach ($macData as $mk => $mv) {
                if (!empty($mv['imei'])) {
                    $imei = $mv['imei'];
                    break;
                }
            }
            if (!empty($imei)) {
                $macNeedData2 = XsUserForbidden::findFirst([
                    'conditions' => 'mac=:mac:',
                    'bind'       => [
                        'mac' => $imei,
                    ]
                ]);
            }
        }

        if (!empty($macNeedData) || !empty($macNeedData2)) {
            $macNeed = 1;
        }
        return [
            $imei,
            $macNeed
        ];
    }

    public function handle()
    {
        $model    = XsUserForbiddenDuration::findFirst([
            'conditions' => 'uid=:uid:',
            'bind'       => [
                'uid' => $this->context->uid,
            ]
        ]);
        $duration = '永久';
        if (!empty($model)) {
            $duration = date('Y-m-d H:i', $model->dateline);
        }

        $mac     = '';
        $imei    = '';
        $macNeed = 0;

        $mac = $this->getMac();
        list($imei, $macNeed) = $this->getImeiAndMacNeed($mac);

        $userInfo = XsUserSettings::findFirst($this->context->uid);
        if ($userInfo) {
            $did = $userInfo->did;
        } else {
            $did = '';
        }


        return [
            'forbidden_duration' => (string) $duration,
            'mac'                => (string) $mac,
            'did'                => (string) $did,
            'macneed'            => (string) $macNeed,
            'income_money'       => '0',
        ];
    }
}
