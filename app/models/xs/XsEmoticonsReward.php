<?php

namespace Imee\Models\Xs;

class XsEmoticonsReward extends BaseModel
{
    public static $uploadFields = [
        'uid'          => 'UID',
        'emoticons_id' => '配置ID',
        'reward_time'  => '下发天数',
    ];
}