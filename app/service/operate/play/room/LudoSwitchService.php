<?php

namespace Imee\Service\Operate\Play\Room;

use Imee\Models\Xs\XsBigarea;

class LudoSwitchService extends SwitchBaseService
{
    public function __construct()
    {
        $this->type = XsBigarea::LAYA_LUDO_ROOM_SWITCH;
        $this->guid = 'roomludoregionswitch';
        $this->field = ['laya_ludo_room_switch', 'laya_ludo_room_switch_diamond'];
    }
}