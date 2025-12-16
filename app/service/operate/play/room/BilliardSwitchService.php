<?php

namespace Imee\Service\Operate\Play\Room;

use Imee\Models\Xs\XsBigarea;

class BilliardSwitchService extends SwitchBaseService
{
    public function __construct()
    {
        $this->type = XsBigarea::LAYA_BILLIARD_ROOM_SWITCH;
        $this->guid = 'roombilliardregionswitch';
        $this->field = ['laya_billiards_room_switch', 'laya_billiards_room_switch_diamond'];
    }
}