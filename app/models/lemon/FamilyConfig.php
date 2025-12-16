<?php

namespace Imee\Models\Lemon;

class FamilyConfig extends BaseModel
{
    public static $primaryKey = 'fid';

    const CERTIFICATE_TYPE_DEFAULT = 'none';
    const CERTIFICATE_TYPE_HOST = 'family_host';
    const CERTIFICATE_TYPE_STAR = 'family_star';
    const CERTIFICATE_TYPE_PERSONAL_HOST = 'independent_host';
    const CERTIFICATE_TYPE_PERSONAL_STAR = 'independent_star';

    const IS_CERTIFICATED = 1;
    const IS_NOT_CERTIFICATED = 0;

    public function initialize()
    {
        parent::initialize();
    }

    public static function getCertTypeAll($type = null): array
    {
        $familyTypeMap = [
            self::CERTIFICATE_TYPE_HOST => '家族主播认证',
            self::CERTIFICATE_TYPE_STAR => '家族明星认证',
        ];

        $personal = [
            self::CERTIFICATE_TYPE_PERSONAL_HOST => '个人主播认证',
            self::CERTIFICATE_TYPE_PERSONAL_STAR => '个人明星认证',
        ];

        if ($type == 'family') {
            return $familyTypeMap;
        }

        if ($type == 'personal') {
            return $personal;
        }

        return $familyTypeMap + $personal;
    }

}
